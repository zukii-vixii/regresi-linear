#!/usr/bin/env bash
# ==============================================================================
#  Regresi Linear — One-shot installer (Laravel 10 + Vue 3 + MySQL + Nginx + SSL)
#  Target  : Fresh Ubuntu 22.04 / 24.04 VPS (root)
#  Source  : Already extracted to /var/www/regresi-linear (from lintascode.com zip)
#  Run     : bash install.sh    (or: DOMAIN=foo.com bash install.sh)
# ==============================================================================
set -euo pipefail

# ----------- Konfigurasi default (boleh di-override via env var) --------------
APP_DIR="${APP_DIR:-/var/www/regresi-linear}"
SERVICE_NAME="${SERVICE_NAME:-regresi-linear}"
DOMAIN="${DOMAIN:-regresi-linear.lintascode.com}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@lintascode.com}"
DB_NAME="${DB_NAME:-regresi_linear_db}"
DB_USER="${DB_USER:-regresi_linear}"
DB_PASS="${DB_PASS:-}"
PHP_VER="8.4"
NODE_MAJOR="20"

# Login default (TER-SEED — sinkron dengan database/seeders/DatabaseSeeder.php)
SEED_ADMIN_EMAIL="admin@local.test"
SEED_ADMIN_PASS="admin123"
SEED_USER_EMAIL="user@local.test"
SEED_USER_PASS="user1234"

# ----------- Helpers ----------------------------------------------------------
say()  { printf "\n\033[1;36m==> %s\033[0m\n" "$*"; }
ok()   { printf "\033[1;32m✓ %s\033[0m\n"  "$*"; }
warn() { printf "\033[1;33m! %s\033[0m\n"  "$*"; }
die()  { printf "\n\033[1;31m✗ %s\033[0m\n" "$*" >&2; exit 1; }

[[ $EUID -eq 0 ]] || die "Harus dijalankan sebagai root (sudo bash install.sh)."

cd "$APP_DIR" 2>/dev/null || die "Folder $APP_DIR tidak ditemukan. Unzip dulu paket dari lintascode.com."
[[ -f "artisan" ]] || die "File artisan tidak ada di $APP_DIR — paket korup atau salah folder."

say "Installer Regresi Linear · domain=$DOMAIN · app_dir=$APP_DIR"

# ----------- 1. Apt prerequisites --------------------------------------------
say "Step 1/9 · Apt update + tooling dasar"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y curl wget git unzip ca-certificates lsb-release software-properties-common gnupg openssl

# ----------- 2. PHP 8.3 + ekstensi -------------------------------------------
say "Step 2/9 · PHP $PHP_VER + ekstensi"
if ! command -v php >/dev/null || ! php -v 2>/dev/null | grep -q "PHP $PHP_VER"; then
  add-apt-repository -y ppa:ondrej/php
  apt-get update -y
fi
apt-get install -y \
  php${PHP_VER}-fpm php${PHP_VER}-cli php${PHP_VER}-common \
  php${PHP_VER}-mysql php${PHP_VER}-mbstring php${PHP_VER}-xml php${PHP_VER}-curl \
  php${PHP_VER}-zip php${PHP_VER}-bcmath php${PHP_VER}-gd php${PHP_VER}-intl \
  php${PHP_VER}-tokenizer php${PHP_VER}-fileinfo
update-alternatives --set php /usr/bin/php${PHP_VER} >/dev/null 2>&1 || true
ok "PHP $(php -r 'echo PHP_VERSION;')"

# ----------- 3. Composer ------------------------------------------------------
say "Step 3/9 · Composer"
if ! command -v composer >/dev/null; then
  curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
  php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet
  rm -f /tmp/composer-setup.php
fi
ok "Composer $(composer --version | awk '{print $3}')"

# ----------- 4. Node.js 20 ----------------------------------------------------
say "Step 4/9 · Node.js $NODE_MAJOR"
if ! command -v node >/dev/null || [[ "$(node -v | cut -d. -f1 | tr -d v)" -lt "$NODE_MAJOR" ]]; then
  curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash -
  apt-get install -y nodejs
fi
ok "Node $(node -v) · npm $(npm -v)"

# ----------- 5. MySQL ---------------------------------------------------------
say "Step 5/9 · MySQL Server"
apt-get install -y mysql-server
systemctl enable --now mysql

if [[ -z "$DB_PASS" ]]; then
  DB_PASS="$(openssl rand -hex 16)"
fi

mysql --protocol=socket -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
ok "DB: $DB_NAME · user: $DB_USER"

# ----------- 6. .env + Composer/NPM build ------------------------------------
say "Step 6/9 · .env, composer install, npm build"

if [[ ! -f "$APP_DIR/.env" ]] || ! grep -q "^APP_KEY=base64:" "$APP_DIR/.env"; then
  cat > "$APP_DIR/.env" <<ENV
APP_NAME="Regresi Linear"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://${DOMAIN}

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

SANCTUM_STATEFUL_DOMAINS=${DOMAIN}
SESSION_DOMAIN=${DOMAIN}
ENV
else
  # update DB credentials & APP_URL kalau .env sudah ada
  sed -i \
    -e "s|^APP_ENV=.*|APP_ENV=production|" \
    -e "s|^APP_DEBUG=.*|APP_DEBUG=false|" \
    -e "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" \
    -e "s|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" \
    -e "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" \
    -e "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" \
    -e "s|^SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=${DOMAIN}|" \
    -e "s|^SESSION_DOMAIN=.*|SESSION_DOMAIN=${DOMAIN}|" \
    "$APP_DIR/.env"
fi

cd "$APP_DIR"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if ! grep -q "^APP_KEY=base64:" "$APP_DIR/.env"; then
  php artisan key:generate --force
fi

# Cek apakah perlu migrate (ada tabel users belum?)
NEED_SEED=0
if ! mysql -u"${DB_USER}" -p"${DB_PASS}" -e "USE ${DB_NAME}; SELECT 1 FROM users LIMIT 1;" >/dev/null 2>&1; then
  NEED_SEED=1
fi

php artisan migrate --force
if [[ $NEED_SEED -eq 1 ]]; then
  php artisan db:seed --force
  ok "Seeder dijalankan (admin & user demo dibuat)"
else
  warn "Tabel users sudah ada — seeder dilewati (data lama dipertahankan)"
fi

# Build frontend
npm ci --no-audit --no-fund
npm run build
ok "Build frontend selesai"

# Cache config
php artisan config:cache
php artisan route:cache 2>/dev/null || true

# Permissions
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R ug+rwx "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

# ----------- 7. Systemd unit (untuk kompatibilitas GitMac restart) -----------
say "Step 7/9 · Systemd unit '$SERVICE_NAME' (oneshot · clear cache + reload php-fpm)"
cat > /etc/systemd/system/${SERVICE_NAME}.service <<UNIT
[Unit]
Description=${SERVICE_NAME} (Laravel maintenance unit — clears cache & reloads php-fpm)
After=network.target mysql.service php${PHP_VER}-fpm.service

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php ${APP_DIR}/artisan optimize:clear
ExecStart=/usr/bin/php ${APP_DIR}/artisan config:cache
ExecStart=/bin/systemctl reload php${PHP_VER}-fpm.service

[Install]
WantedBy=multi-user.target
UNIT

systemctl daemon-reload
systemctl enable --now ${SERVICE_NAME}.service
ok "Service '$SERVICE_NAME' aktif (restart akan refresh cache + reload php-fpm)"

# ----------- 8. Nginx --------------------------------------------------------
say "Step 8/9 · Nginx vhost"
apt-get install -y nginx
systemctl enable --now nginx

cat > /etc/nginx/sites-available/${SERVICE_NAME} <<NGX
server {
    listen 80;
    server_name ${DOMAIN};
    root ${APP_DIR}/public;
    index index.php index.html;

    client_max_body_size 25m;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/php${PHP_VER}-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
NGX

ln -sf /etc/nginx/sites-available/${SERVICE_NAME} /etc/nginx/sites-enabled/${SERVICE_NAME}
[[ -e /etc/nginx/sites-enabled/default ]] && rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx
ok "Nginx vhost aktif untuk $DOMAIN"

# ----------- 9. SSL (Certbot) ------------------------------------------------
say "Step 9/9 · Certbot SSL untuk $DOMAIN"
apt-get install -y certbot python3-certbot-nginx
if certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos -m "${ADMIN_EMAIL}" --redirect; then
  ok "SSL terpasang"
else
  warn "Certbot gagal (kemungkinan DNS ${DOMAIN} belum mengarah ke server ini). App tetap jalan via HTTP. Jalankan ulang: certbot --nginx -d ${DOMAIN}"
fi

systemctl reload nginx

# ----------- Verifikasi ------------------------------------------------------
say "Verifikasi akhir"
sleep 1
HTTP_CODE="$(curl -sk -o /dev/null -w '%{http_code}' "https://${DOMAIN}/" || echo 000)"
HTTP_API="$(curl -sk -o /dev/null -w '%{http_code}' "https://${DOMAIN}/api/health" || echo 000)"
[[ "$HTTP_CODE" == "000" ]] && HTTP_CODE="$(curl -s -o /dev/null -w '%{http_code}' "http://${DOMAIN}/" || echo 000)"
[[ "$HTTP_API"  == "000" ]] && HTTP_API="$(curl -s -o /dev/null -w '%{http_code}' "http://${DOMAIN}/api/health" || echo 000)"

cat <<EOF

============================================================
  ✅  REGRESI LINEAR — INSTALL SELESAI
============================================================
  URL aplikasi   : https://${DOMAIN}
  HTTP root      : ${HTTP_CODE}
  HTTP /api/health: ${HTTP_API}

  App dir        : ${APP_DIR}
  Service        : ${SERVICE_NAME}  (systemctl restart ${SERVICE_NAME})
  Nginx vhost    : /etc/nginx/sites-available/${SERVICE_NAME}
  PHP-FPM        : php${PHP_VER}-fpm

  Database
    Name         : ${DB_NAME}
    User         : ${DB_USER}
    Password     : ${DB_PASS}

  🔑 LOGIN DEFAULT (GANTI segera setelah login pertama!)
    Admin
      Email      : ${SEED_ADMIN_EMAIL}
      Password   : ${SEED_ADMIN_PASS}
    User biasa
      Email      : ${SEED_USER_EMAIL}
      Password   : ${SEED_USER_PASS}
============================================================

EOF
