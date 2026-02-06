#!/usr/bin/env bash
set -euo pipefail

# Tutor81 LMS - VPS setup (Ubuntu/Debian + RHEL-like: AlmaLinux/Rocky/CentOS)
# Run from the project root: sudo ./deployment/setup.sh

if [[ $EUID -ne 0 ]]; then
  echo "ERROR: run as root (use sudo)" >&2
  exit 1
fi

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"

APP_NAME="tutor81-lms"
DOMAIN=""
PORT=""
APP_DIR_OVERRIDE=""
SSL_MODE="prompt"

usage() {
  cat <<EOF
Usage: sudo ./deployment/setup.sh [options]

Options:
  --app-name <name>   Systemd/nginx/app instance name (default: tutor81-lms)
  --app-dir <dir>     Install directory (default: /opt/<app-name>)
  --domain <domain>   Domain for nginx server_name (e.g. lms.tutor81.com)
  --port <port>       Local port the Node app listens on (default: 5000)
  --ssl <mode>        HTTPS certbot mode: prompt|enable|skip (default: prompt)
  --help              Show this help

Examples:
  # Production
  sudo ./deployment/setup.sh --domain lms.tutor81.com

  # Staging (same server)
  sudo ./deployment/setup.sh --app-name tutor81-lms-staging --domain stg.lms.tutor81.com --port 5001

  # Use a dedicated folder name (avoid confusion)
  sudo ./deployment/setup.sh --app-name tutor81-lms --app-dir /opt/LMS --domain lms.tutor81.com

  # Non-interactive (skip certbot prompt)
  sudo ./deployment/setup.sh --app-name tutor81-lms --app-dir /opt/LMS --domain lms.tutor81.com --ssl skip
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --app-name)
      APP_NAME="${2:-}"; shift 2 ;;
    --app-dir)
      APP_DIR_OVERRIDE="${2:-}"; shift 2 ;;
    --domain)
      DOMAIN="${2:-}"; shift 2 ;;
    --port)
      PORT="${2:-}"; shift 2 ;;
    --ssl)
      SSL_MODE="${2:-}"; shift 2 ;;
    --help|-h)
      usage; exit 0 ;;
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

case "${SSL_MODE}" in
  prompt|enable|skip) ;; 
  *)
    echo "ERROR: --ssl must be one of: prompt|enable|skip" >&2
    exit 1
    ;;
esac

APP_DIR="/opt/${APP_NAME}"
if [[ -n "${APP_DIR_OVERRIDE}" ]]; then
  APP_DIR="${APP_DIR_OVERRIDE}"
fi
ENV_FILE="/etc/${APP_NAME}.env"
SYSTEMD_UNIT="/etc/systemd/system/${APP_NAME}.service"

need_cmd() { command -v "$1" >/dev/null 2>&1; }

os_id() {
  if [[ -r /etc/os-release ]]; then
    . /etc/os-release
    echo "${ID:-unknown}"
  else
    echo "unknown"
  fi
}

is_rhel_like() {
  case "$(os_id)" in
    almalinux|rocky|centos|rhel|fedora) return 0 ;;
    *) return 1 ;;
  esac
}

NGINX_SITE=""
NGINX_LINK=""
if is_rhel_like; then
  # RHEL-like distros use /etc/nginx/conf.d/*.conf
  NGINX_SITE="/etc/nginx/conf.d/${APP_NAME}.conf"
else
  # Debian/Ubuntu use sites-available/sites-enabled
  NGINX_SITE="/etc/nginx/sites-available/${APP_NAME}"
  NGINX_LINK="/etc/nginx/sites-enabled/${APP_NAME}"
fi

echo "== Tutor81 LMS setup =="
echo "Project root: ${PROJECT_ROOT}"
echo "App name: ${APP_NAME}"
echo "App dir: ${APP_DIR}"
echo "Env file: ${ENV_FILE}"
echo "Systemd unit: ${SYSTEMD_UNIT}"

echo "\n[1/8] Installing OS packages..."
if is_rhel_like; then
  dnf -y install ca-certificates curl rsync nginx
  systemctl enable nginx --now
else
  apt-get update -y
  apt-get install -y ca-certificates curl gnupg rsync nginx
fi

# Node.js 20 LTS via NodeSource (idempotent)
if ! need_cmd node; then
  echo "\nInstalling Node.js 20..."
  if is_rhel_like; then
    curl -fsSL https://rpm.nodesource.com/setup_20.x | bash -
    dnf -y install nodejs
  else
    mkdir -p /etc/apt/keyrings
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
    NODE_MAJOR=20
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_${NODE_MAJOR}.x nodistro main" > /etc/apt/sources.list.d/nodesource.list
    apt-get update -y
    apt-get install -y nodejs
  fi
fi

node -v
npm -v

echo "\n[2/8] Creating app user (tutor81)..."
if ! id -u tutor81 >/dev/null 2>&1; then
  useradd --system --create-home --home-dir /home/tutor81 --shell /bin/bash tutor81
fi

echo "\n[3/8] Copying project to ${APP_DIR}..."
mkdir -p "${APP_DIR}"
rsync -a --delete \
  --exclude node_modules \
  --exclude dist \
  --exclude .git \
  "${PROJECT_ROOT}/" "${APP_DIR}/"
chown -R tutor81:tutor81 "${APP_DIR}"

echo "\n[4/8] Creating env file ${ENV_FILE}..."
if [[ -f "${ENV_FILE}" ]]; then
  echo "Env file already exists: ${ENV_FILE} (keeping it)"
else
  echo "Inserisci i valori richiesti. Puoi lasciare vuoto OVH se non ti serve subito."

  if [[ -z "${DOMAIN}" ]]; then
    read -r -p "DOMAIN (es. lms.tutor81.com): " DOMAIN
  fi
  if [[ -z "${PORT}" ]]; then
    read -r -p "PORT [5000]: " PORT
  fi
  PORT=${PORT:-5000}

  read -r -p "DATABASE_URL (postgres://...) [leave empty to create local Postgres]: " DATABASE_URL

  if [[ -z "${DATABASE_URL}" ]]; then
    echo "\nNessun DATABASE_URL fornito. Vuoi installare PostgreSQL su questo server (consigliato per partire)?"
    read -r -p "Install PostgreSQL locally? (y/N): " INSTALL_PG
    if [[ "${INSTALL_PG}" =~ ^[Yy]$ ]]; then
      echo "\nInstalling PostgreSQL..."
      if is_rhel_like; then
        dnf -y install postgresql-server postgresql-contrib
        if command -v postgresql-setup >/dev/null 2>&1; then
          postgresql-setup --initdb || true
        fi
        systemctl enable postgresql --now
      else
        apt-get install -y postgresql postgresql-contrib
        systemctl enable postgresql --now
      fi

      read -r -p "Postgres DB name [tutor81]: " PG_DB
      PG_DB=${PG_DB:-tutor81}
      read -r -p "Postgres DB user [tutor81]: " PG_USER
      PG_USER=${PG_USER:-tutor81}
      read -r -s -p "Postgres DB password: " PG_PASS
      echo ""

      # Create user + db if missing
      sudo -u postgres psql -v ON_ERROR_STOP=1 <<SQL
DO $$
BEGIN
  IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '${PG_USER}') THEN
    CREATE ROLE ${PG_USER} LOGIN PASSWORD '${PG_PASS}';
  END IF;
END
$$;

DO $$
BEGIN
  IF NOT EXISTS (SELECT FROM pg_database WHERE datname = '${PG_DB}') THEN
    CREATE DATABASE ${PG_DB} OWNER ${PG_USER};
  END IF;
END
$$;
SQL

      DATABASE_URL="postgres://${PG_USER}:${PG_PASS}@127.0.0.1:5432/${PG_DB}"
      echo "Generated DATABASE_URL: ${DATABASE_URL}"
    else
      echo "WARNING: proceeding without DATABASE_URL. Database-backed routes and auth will be disabled." >&2
      DATABASE_URL=""
    fi
  fi
  echo "\nAuth (Replit OIDC)"
  echo "- Se vuoi solo fare smoke test (/api/health), puoi lasciare vuoto tutto."
  read -r -p "SESSION_SECRET (stringa lunga) [optional]: " SESSION_SECRET
  read -r -p "REPL_ID (Replit OIDC client id) [optional]: " REPL_ID
  read -r -p "ISSUER_URL [https://replit.com/oidc]: " ISSUER_URL
  ISSUER_URL=${ISSUER_URL:-https://replit.com/oidc}

  echo "\nStaging (opzionale)"
  echo "- Se abiliti DISABLE_AUTH, il login Replit viene bypassato (solo staging)."
  read -r -p "DISABLE_AUTH [false]: " DISABLE_AUTH
  DISABLE_AUTH=${DISABLE_AUTH:-false}
  DEV_USER_ID=""
  DEV_ROLE=""
  DEV_IDCOMPANY=""
  DEV_EMAIL=""
  DEV_FIRST_NAME=""
  DEV_LAST_NAME=""
  DEV_PROFILE_IMAGE_URL=""
  if [[ "${DISABLE_AUTH}" =~ ^([Tt][Rr][Uu][Ee]|1|[Yy][Ee][Ss])$ ]]; then
    read -r -p "DEV_USER_ID [dev-user]: " DEV_USER_ID
    DEV_USER_ID=${DEV_USER_ID:-dev-user}
    read -r -p "DEV_ROLE [1000]: " DEV_ROLE
    DEV_ROLE=${DEV_ROLE:-1000}
    read -r -p "DEV_IDCOMPANY (optional): " DEV_IDCOMPANY
    read -r -p "DEV_EMAIL [dev@localhost]: " DEV_EMAIL
    DEV_EMAIL=${DEV_EMAIL:-dev@localhost}
    read -r -p "DEV_FIRST_NAME [Dev]: " DEV_FIRST_NAME
    DEV_FIRST_NAME=${DEV_FIRST_NAME:-Dev}
    read -r -p "DEV_LAST_NAME [User]: " DEV_LAST_NAME
    DEV_LAST_NAME=${DEV_LAST_NAME:-User}
    read -r -p "DEV_PROFILE_IMAGE_URL (optional): " DEV_PROFILE_IMAGE_URL
  fi

  read -r -p "OVH_DB_HOST (optional): " OVH_DB_HOST
  read -r -p "OVH_DB_PORT [3306]: " OVH_DB_PORT
  OVH_DB_PORT=${OVH_DB_PORT:-3306}
  read -r -p "OVH_DB_USER (optional): " OVH_DB_USER
  read -r -s -p "OVH_DB_PASSWORD (optional): " OVH_DB_PASSWORD
  echo ""
  read -r -p "OVH_DB_NAME (optional): " OVH_DB_NAME

  cat > "${ENV_FILE}" <<EOF
NODE_ENV=production
PORT=${PORT}
DATABASE_URL=${DATABASE_URL}
SESSION_SECRET=${SESSION_SECRET}
REPL_ID=${REPL_ID}
ISSUER_URL=${ISSUER_URL}
DISABLE_AUTH=${DISABLE_AUTH}
DEV_USER_ID=${DEV_USER_ID}
DEV_ROLE=${DEV_ROLE}
DEV_IDCOMPANY=${DEV_IDCOMPANY}
DEV_EMAIL=${DEV_EMAIL}
DEV_FIRST_NAME=${DEV_FIRST_NAME}
DEV_LAST_NAME=${DEV_LAST_NAME}
DEV_PROFILE_IMAGE_URL=${DEV_PROFILE_IMAGE_URL}
OVH_DB_HOST=${OVH_DB_HOST}
OVH_DB_PORT=${OVH_DB_PORT}
OVH_DB_USER=${OVH_DB_USER}
OVH_DB_PASSWORD=${OVH_DB_PASSWORD}
OVH_DB_NAME=${OVH_DB_NAME}
OVH_DB_CONNECT_TIMEOUT=10000
EOF

  chmod 600 "${ENV_FILE}"
fi

# Read PORT back from env file for nginx (unless explicitly provided)
if [[ -z "${PORT}" ]]; then
  PORT="$(grep -E '^PORT=' -m1 "${ENV_FILE}" | cut -d= -f2-)"
fi

if [[ -z "${DOMAIN}" ]]; then
  read -r -p "DOMAIN for nginx (es. lms.tutor81.com): " DOMAIN
fi

echo "\n[5/8] Installing dependencies & building app..."
sudo -u tutor81 bash -lc "cd '${APP_DIR}' && npm ci"
sudo -u tutor81 bash -lc "cd '${APP_DIR}' && npm run build"

echo "\n[6/8] Configuring systemd service..."
cp "${APP_DIR}/deployment/templates/tutor81-lms.service" "${SYSTEMD_UNIT}"
sed -i "s#__APP_DIR__#${APP_DIR}#g" "${SYSTEMD_UNIT}"
sed -i "s#__ENV_FILE__#${ENV_FILE}#g" "${SYSTEMD_UNIT}"
systemctl daemon-reload
systemctl enable "${APP_NAME}" --now

echo "\n[7/8] Configuring nginx reverse proxy..."
SITE_TEMPLATE="${APP_DIR}/deployment/templates/nginx-site.conf"
cp "${SITE_TEMPLATE}" "${NGINX_SITE}"
sed -i "s/__DOMAIN__/${DOMAIN}/g" "${NGINX_SITE}"
sed -i "s/__PORT__/${PORT}/g" "${NGINX_SITE}"

if ! is_rhel_like; then
  # disable default site if present
  if [[ -e /etc/nginx/sites-enabled/default ]]; then
    rm -f /etc/nginx/sites-enabled/default
  fi

  ln -sf "${NGINX_SITE}" "${NGINX_LINK}"
fi

# SELinux (RHEL-like): allow nginx to proxy to localhost
if is_rhel_like && command -v getenforce >/dev/null 2>&1; then
  if [[ "$(getenforce)" != "Disabled" ]]; then
    if command -v setsebool >/dev/null 2>&1; then
      setsebool -P httpd_can_network_connect 1 || true
    fi
  fi
fi

nginx -t
systemctl restart nginx

# Firewall (RHEL-like): allow HTTP/HTTPS if firewalld is active
if is_rhel_like && command -v firewall-cmd >/dev/null 2>&1; then
  if firewall-cmd --state >/dev/null 2>&1; then
    firewall-cmd --permanent --add-service=http || true
    firewall-cmd --permanent --add-service=https || true
    firewall-cmd --reload || true
  fi
fi

echo "\n[8/8] (Optional) HTTPS certificate..."

SSL=""
if [[ "${SSL_MODE}" == "prompt" ]]; then
  echo "\nOptional: enable HTTPS with certbot?"
  read -r -p "Install certbot and request certificate for ${DOMAIN}? (y/N): " SSL
elif [[ "${SSL_MODE}" == "enable" ]]; then
  SSL="y"
else
  SSL="n"
fi

if [[ "${SSL}" =~ ^[Yy]$ ]]; then
  if is_rhel_like; then
    dnf -y install certbot python3-certbot-nginx || true
  else
    apt-get install -y certbot python3-certbot-nginx
  fi
  certbot --nginx -d "${DOMAIN}"
fi

echo "\nDONE. Quick checks:"
echo "- systemctl status ${APP_NAME}"
echo "- journalctl -u ${APP_NAME} -f"
echo "- https://${DOMAIN}/api/health  => {\"ok\":true}"
