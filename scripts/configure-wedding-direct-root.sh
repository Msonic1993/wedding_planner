#!/usr/bin/env bash

set -Eeuo pipefail

APP_ROOT="${APP_ROOT:-/var/www/wedding-planet}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
SERVER_NAME="${SERVER_NAME:-_}"
NGINX_SITE="/etc/nginx/sites-available/wedding-planet"

fail() {
    printf 'ERROR: %s\n' "$*" >&2
    exit 1
}

[[ "${EUID}" -eq 0 ]] || fail 'Uruchom skrypt jako root.'
[[ -d "${APP_ROOT}" ]] || fail "Nie znaleziono katalogu: ${APP_ROOT}"
command -v nginx >/dev/null 2>&1 || fail 'Nginx nie jest zainstalowany.'

if ! id -u "${DEPLOY_USER}" >/dev/null 2>&1; then
    useradd --create-home --shell /bin/bash "${DEPLOY_USER}"
fi
usermod --append --groups www-data "${DEPLOY_USER}"

printf 'Usuwanie wyłącznie pustych katalogów starego układu...\n'
rmdir "${APP_ROOT}/current/public" 2>/dev/null || true
rmdir "${APP_ROOT}/current" 2>/dev/null || true
rmdir "${APP_ROOT}/releases" 2>/dev/null || true
rmdir "${APP_ROOT}/shared/storage" 2>/dev/null || true
rmdir "${APP_ROOT}/shared/bootstrap/cache" 2>/dev/null || true
rmdir "${APP_ROOT}/shared/bootstrap" 2>/dev/null || true
rmdir "${APP_ROOT}/shared" 2>/dev/null || true

printf 'Tworzenie katalogów Laravel...\n'
install -d -o "${DEPLOY_USER}" -g www-data -m 2775 \
    "${APP_ROOT}/public" \
    "${APP_ROOT}/storage" \
    "${APP_ROOT}/storage/app/public" \
    "${APP_ROOT}/storage/app/private" \
    "${APP_ROOT}/storage/framework/cache" \
    "${APP_ROOT}/storage/framework/sessions" \
    "${APP_ROOT}/storage/framework/views" \
    "${APP_ROOT}/storage/logs" \
    "${APP_ROOT}/bootstrap/cache"

PHP_BIN_VERSION="$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;' 2>/dev/null || true)"
[[ -n "${PHP_BIN_VERSION}" ]] || fail 'PHP nie jest zainstalowane.'
PHP_FPM_SOCKET="$(find /run/php -maxdepth 1 -type s -name 'php*-fpm.sock' -print -quit || true)"
[[ -n "${PHP_FPM_SOCKET}" ]] || PHP_FPM_SOCKET="/run/php/php${PHP_BIN_VERSION}-fpm.sock"

cat > "/etc/php/${PHP_BIN_VERSION}/fpm/conf.d/99-wedding-planet.ini" <<'EOF'
upload_max_filesize = 20M
post_max_size = 25M
memory_limit = 256M
max_execution_time = 60
EOF

printf 'Konfiguracja Nginx...\n'
cat > "${NGINX_SITE}" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${SERVER_NAME};

    root ${APP_ROOT}/public;
    index index.php;
    charset utf-8;
    client_max_body_size 25M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${PHP_FPM_SOCKET};
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

ln -sfn "${NGINX_SITE}" /etc/nginx/sites-enabled/wedding-planet
rm -f /etc/nginx/sites-enabled/default

chown -R "${DEPLOY_USER}:www-data" "${APP_ROOT}"
find "${APP_ROOT}" -type d -exec chmod 2775 {} +

systemctl enable --now "php${PHP_BIN_VERSION}-fpm" 2>/dev/null || systemctl enable --now php-fpm
nginx -t
systemctl enable --now nginx
systemctl reload nginx

printf '\nGotowe.\n'
printf 'Katalog aplikacji: %s\n' "${APP_ROOT}"
printf 'Document root Nginx: %s/public\n' "${APP_ROOT}"
printf 'PHP-FPM socket: %s\n' "${PHP_FPM_SOCKET}"
