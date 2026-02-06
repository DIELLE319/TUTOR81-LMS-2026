#!/usr/bin/env bash
set -euo pipefail

# Roll back to a previous release by repointing /opt/<app> symlink.

if [[ ${EUID:-$(id -u)} -ne 0 ]]; then
  echo "ERROR: run as root (use sudo)" >&2
  exit 1
fi

APP_NAME="tutor81-lms"
TO=""

usage() {
  cat <<EOF
Usage: sudo ./deployment/rollback.sh [options]

Options:
  --app-name <name>   systemd unit name (default: tutor81-lms)
  --to <release>      release folder name under /opt/<app>-releases (optional)
  --help              show this help

If --to is omitted, it rolls back to the previous release (2nd newest).

Examples:
  sudo ./deployment/rollback.sh --app-name tutor81-lms
  sudo ./deployment/rollback.sh --app-name tutor81-lms --to 20260206123000-main
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --app-name)
      APP_NAME="${2:-}"; shift 2;;
    --to)
      TO="${2:-}"; shift 2;;
    --help|-h)
      usage; exit 0;;
    *)
      echo "ERROR: Unknown argument: $1" >&2
      usage
      exit 1
      ;;
  esac
done

APP_LINK="/opt/${APP_NAME}"
RELEASES_DIR="/opt/${APP_NAME}-releases"

if [[ ! -d "${RELEASES_DIR}" ]]; then
  echo "ERROR: releases dir not found: ${RELEASES_DIR}" >&2
  exit 1
fi

if [[ -n "${TO}" ]]; then
  TARGET="${RELEASES_DIR}/${TO}"
  if [[ ! -d "${TARGET}" ]]; then
    echo "ERROR: release not found: ${TARGET}" >&2
    echo "Available releases:" >&2
    ls -1 "${RELEASES_DIR}" >&2 || true
    exit 1
  fi
else
  # previous release (2nd newest)
  TARGET="$(ls -1dt "${RELEASES_DIR}"/* 2>/dev/null | sed -n '2p')"
  if [[ -z "${TARGET}" ]]; then
    echo "ERROR: no previous release found" >&2
    ls -1dt "${RELEASES_DIR}"/* 2>/dev/null || true
    exit 1
  fi
fi

if [[ -e "${APP_LINK}" && ! -L "${APP_LINK}" ]]; then
  echo "ERROR: ${APP_LINK} exists and is not a symlink; refusing to switch" >&2
  exit 1
fi

echo "Rolling back ${APP_NAME} to: ${TARGET}"
ln -sfn "${TARGET}" "${APP_LINK}"
systemctl restart "${APP_NAME}"

echo "DONE"
echo "- Current: $(readlink "${APP_LINK}" || true)"
