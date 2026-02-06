#!/usr/bin/env bash
set -euo pipefail

# Creates an immutable release directory and atomically switches /opt/<app> symlink to it.
# Intended to be run on the server.

if [[ ${EUID:-$(id -u)} -ne 0 ]]; then
  echo "ERROR: run as root (use sudo)" >&2
  exit 1
fi

APP_NAME="tutor81-lms"
REF="main"
REPO=""
KEEP="8"

usage() {
  cat <<EOF
Usage: sudo ./deployment/release.sh [options]

Options:
  --app-name <name>   systemd unit name (default: tutor81-lms)
  --ref <ref>         git ref/branch/tag/commit (default: main)
  --repo <url|path>   git repository URL or path; if omitted, uses current folder content
  --keep <n>          how many releases to keep (default: 8)
  --help              show this help

Examples:
  # Deploy from current folder content (rsync) to a new release:
  sudo ./deployment/release.sh --app-name tutor81-lms --ref local

  # Deploy from git (recommended):
  sudo ./deployment/release.sh --app-name tutor81-lms --repo https://github.com/ORG/REPO.git --ref main
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --app-name)
      APP_NAME="${2:-}"; shift 2;;
    --ref)
      REF="${2:-}"; shift 2;;
    --repo)
      REPO="${2:-}"; shift 2;;
    --keep)
      KEEP="${2:-}"; shift 2;;
    --help|-h)
      usage; exit 0;;
    *)
      echo "ERROR: Unknown argument: $1" >&2
      usage
      exit 1
      ;;
  esac
done

if [[ -z "${APP_NAME}" ]]; then
  echo "ERROR: --app-name cannot be empty" >&2
  exit 1
fi

if ! command -v systemctl >/dev/null 2>&1; then
  echo "ERROR: systemctl not found" >&2
  exit 1
fi

if ! command -v rsync >/dev/null 2>&1; then
  echo "ERROR: rsync not found" >&2
  exit 1
fi

APP_LINK="/opt/${APP_NAME}"
RELEASES_DIR="/opt/${APP_NAME}-releases"
mkdir -p "${RELEASES_DIR}"

stamp="$(date +%Y%m%d%H%M%S)"
ref_sanitized="${REF//\//-}"
RELEASE_DIR="${RELEASES_DIR}/${stamp}-${ref_sanitized}"

if [[ -e "${RELEASE_DIR}" ]]; then
  echo "ERROR: release dir already exists: ${RELEASE_DIR}" >&2
  exit 1
fi

echo "== Deploy release =="
echo "App: ${APP_NAME}"
echo "Ref: ${REF}"
echo "Release: ${RELEASE_DIR}"

echo "\n[1/4] Preparing release directory..."
mkdir -p "${RELEASE_DIR}"

echo "\n[2/4] Populating source..."
if [[ -n "${REPO}" ]]; then
  if ! command -v git >/dev/null 2>&1; then
    echo "ERROR: git not found (needed for --repo)" >&2
    exit 1
  fi
  git clone --depth 1 --branch "${REF}" "${REPO}" "${RELEASE_DIR}"
else
  # Copy from the folder where this script is executed (repo root assumed)
  SRC_DIR="$(pwd)"
  if [[ ! -f "${SRC_DIR}/package.json" ]]; then
    echo "ERROR: package.json not found in ${SRC_DIR}. Run this from repo root or pass --repo." >&2
    exit 1
  fi
  rsync -a --delete \
    --exclude node_modules \
    --exclude dist \
    --exclude .git \
    "${SRC_DIR}/" "${RELEASE_DIR}/"
fi

chown -R tutor81:tutor81 "${RELEASE_DIR}"

echo "\n[3/4] Building (npm ci + build)..."
sudo -u tutor81 bash -lc "cd '${RELEASE_DIR}' && npm ci"
sudo -u tutor81 bash -lc "cd '${RELEASE_DIR}' && npm run build"

echo "\n[4/4] Switching current symlink and restarting service..."

# If a legacy non-symlink directory exists at /opt/<app>, preserve it once.
if [[ -e "${APP_LINK}" && ! -L "${APP_LINK}" ]]; then
  legacy_dir="${RELEASES_DIR}/${stamp}-legacy"
  echo "Found legacy directory at ${APP_LINK}. Moving it to ${legacy_dir}"
  mv "${APP_LINK}" "${legacy_dir}"
fi

ln -sfn "${RELEASE_DIR}" "${APP_LINK}"

systemctl restart "${APP_NAME}"

echo "\nCleanup: keeping last ${KEEP} releases..."
# shellcheck disable=SC2012
ls -1dt "${RELEASES_DIR}"/* 2>/dev/null | tail -n "+$((KEEP+1))" | while read -r old; do
  rm -rf "${old}" || true
done

echo "\nDONE"
echo "- Current: $(readlink "${APP_LINK}" || true)"
echo "- Status: systemctl status ${APP_NAME}"
