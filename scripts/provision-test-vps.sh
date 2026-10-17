#!/usr/bin/env bash

set -Eeuo pipefail

APP_NAME="wedding-planet"
APP_ROOT="/var/www/${APP_NAME}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
SERVER_NAME="${SERVER_NAME:-_}"
REDIS_MAX_MEMORY="${REDIS_MAX_MEMORY:-128mb}"

log() {
    printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"
}

fail() {
    printf 'ERROR: %s\n' "$*" >&2
    exit 1
}

[[ "${EUID}" -eq 0 ]] || fail 'Uruchom skrypt jako root: sudo bash provision-test-vps.sh'

if [[ ! -r /etc/os-release ]]; then
    fail 'Nie znaleziono /etc/os-release; obsługiwany jest Ubuntu.'
fi

# shellcheck disable=SC1091
source /etc/os-release
[[ "${ID:-}" == 'ubuntu' ]] || fail "Obsługiwany jest Ubuntu, wykryto: ${ID:-unknown}."

export DEBIAN_FRONTEND=noninteractive

log 'Aktualizacja listy pakietów.'
apt-get update

log 'Instalacja usług i narzędzi systemowych.'
apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    nginx \
    redis-server \
    rsync \
    unzip \
    ufw \
    software-properties-common

PHP_PACKAGES=(
    php-cli
    php-fpm
    php-bcmath
    php-curl
    php-gd
    php-intl
    php-mbstring
    php-mysql
    php-redis
    php-xml
    php-zip
)

# Prefer PHP 8.4 when the distribution provides it. If the default Ubuntu
# version is newer (for example 8.5), it is also valid for Laravel ^8.4.
PHP_VERSIONED_PACKAGES=(
    php8.4-cli
    php8.4-fpm
    php8.4-bcmath
    php8.4-curl
    php8.4-gd
    php8.4-intl
    php8.4-mbstring
    php8.4-mysql
    php8.4-redis
    php8.4-xml
    php8.4-zip
)

if apt-cache show php8.4-cli >/dev/null 2>&1; then
    log 'Instalacja PHP 8.4 i rozszerzeń Laravel.'
    apt-get install -y --no-install-recommends "${PHP_VERSIONED_PACKAGES[@]}"
else
    log 'Instalacja domyślnej wersji PHP z repozytorium Ubuntu.'
    apt-get install -y --no-install-recommends "${PHP_PACKAGES[@]}"
fi

PHP_BIN_VERSION="$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
if [[ "${PHP_BIN_VERSION}" < '8.4' ]]; then
    fail "Wymagane jest PHP >= 8.4, wykryto ${PHP_BIN_VERSION}."
fi

log 'Instalacja Composera z weryfikacją sygnatury SHA-384.'
composer_installer="$(mktemp)"
trap 'rm -f "${composer_installer}"' EXIT
curl -fsSL https://getcomposer.org/installer -o "${composer_installer}"
expected_signature="$(curl -fsSL https://composer.github.io/installer.sig)"
actual_signature="$(sha384sum "${composer_installer}" | awk '{print $1}')"
[[ "${expected_signature}" == "${actual_signature}" ]] || fail 'Nieprawidłowa sygnatura instalatora Composera.'
php "${composer_installer}" --install-dir=/usr/local/bin --filename=composer

log "Przygotowanie katalogów aplikacji dla użytkownika ${DEPLOY_USER}."
if ! id -u "${DEPLOY_USER}" >/dev/null 2>&1; then
    useradd --create-home --shell /bin/bash "${DEPLOY_USER}"
fi
usermod --append --groups www-data "${DEPLOY_USER}"
install -d -o "${DEPLOY_USER}" -g www-data -m 2775 "${APP_ROOT}/public"
install -d -o "${DEPLOY_USER}" -g www-data -m 2775 "${APP_ROOT}/storage" \
    "${APP_ROOT}/storage/app/public" \
    "${APP_ROOT}/storage/app/private" \
    "${APP_ROOT}/storage/framework/cache" \
    "${APP_ROOT}/storage/framework/sessions" \
    "${APP_ROOT}/storage/framework/views" \
    "${APP_ROOT}/storage/logs" \
    "${APP_ROOT}/bootstrap/cache"

cat > "/etc/php/${PHP_BIN_VERSION}/fpm/conf.d/99-wedding-planet.ini" <<'EOF'
upload_max_filesize = 20M
post_max_size = 25M
memory_limit = 256M
max_execution_time = 60
EOF

log 'Konfiguracja Redis tylko na localhost z limitem pamięci.'
redis_config='/etc/redis/redis.conf'
if grep -qE '^bind ' "${redis_config}"; then
    sed -i -E 's/^bind .*/bind 127.0.0.1 ::1/' "${redis_config}"
else
    printf '\nbind 127.0.0.1 ::1\n' >> "${redis_config}"
fi
if grep -qE '^protected-mode ' "${redis_config}"; then
    sed -i -E 's/^protected-mode .*/protected-mode yes/' "${redis_config}"
else
    printf 'protected-mode yes\n' >> "${redis_config}"
fi
if grep -qE '^supervised ' "${redis_config}"; then
    sed -i -E 's/^supervised .*/supervised systemd/' "${redis_config}"
else
    printf 'supervised systemd\n' >> "${redis_config}"
fi
if grep -qE '^maxmemory ' "${redis_config}"; then
    sed -i -E "s/^maxmemory .*/maxmemory ${REDIS_MAX_MEMORY}/" "${redis_config}"
else
    printf 'maxmemory %s\n' "${REDIS_MAX_MEMORY}" >> "${redis_config}"
fi
if grep -qE '^maxmemory-policy ' "${redis_config}"; then
    sed -i -E 's/^maxmemory-policy .*/maxmemory-policy noeviction/' "${redis_config}"
else
    printf 'maxmemory-policy noeviction\n' >> "${redis_config}"
fi

log 'Konfiguracja Nginx dla Laravel public/ i PHP-FPM.'
rm -f /etc/nginx/sites-enabled/default
cat > /etc/nginx/sites-available/wedding-planet <<EOF
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
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF
ln -sfn /etc/nginx/sites-available/wedding-planet /etc/nginx/sites-enabled/wedding-planet

# Ubuntu normally exposes /run/php/php-fpm.sock only through the versioned
# pool socket. Use the installed version explicitly when that alias is absent.
PHP_FPM_SOCKET="$(find /run/php -maxdepth 1 -type s -name 'php*-fpm.sock' -print -quit || true)"
[[ -n "${PHP_FPM_SOCKET}" ]] || PHP_FPM_SOCKET="/run/php/php${PHP_BIN_VERSION}-fpm.sock"
sed -i "s#unix:/run/php/php-fpm.sock#unix:${PHP_FPM_SOCKET}#" /etc/nginx/sites-available/wedding-planet

log 'Włączenie usług.'
systemctl enable --now redis-server
systemctl enable --now "php${PHP_BIN_VERSION}-fpm" 2>/dev/null || systemctl enable --now php-fpm
nginx -t
systemctl enable --now nginx
systemctl reload nginx

log 'Konfiguracja zapory sieciowej.'
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

chown -R "${DEPLOY_USER}:www-data" "${APP_ROOT}"
find "${APP_ROOT}" -type d -exec chmod 2775 {} +
find "${APP_ROOT}" -type f -exec chmod 0664 {} +

log 'Provisioning zakończony.'
printf 'PHP: %s\n' "${PHP_BIN_VERSION}"
printf 'Composer: %s\n' "$(composer --version | head -n 1)"
printf 'Deploy user: %s\n' "${DEPLOY_USER}"
printf 'App root: %s\n' "${APP_ROOT}"
printf 'Następny krok: dodaj klucz SSH GitHub Actions do /home/%s/.ssh/authorized_keys.\n' "${DEPLOY_USER}"
