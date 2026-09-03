#!/usr/bin/env bash
#
# Idempotent development environment setup for happy-horizon/actions.
#
# This repository is a CI/toolkit repo: reusable GitHub Actions workflows
# (.github/workflows), the PHP horizon-deploy toolkit (horizon-deploy/), and Bash
# helper scripts (bin/). There is no long-running service, so this script only
# prepares the toolchain used to develop, lint and test those parts:
#
#   - PHP 8.4 CLI + extensions and Composer  -> run/lint the horizon-deploy toolkit
#   - shellcheck                             -> lint bin/ scripts
#   - actionlint + yamllint                  -> lint .github/workflows
#   - horizon-deploy runtime (composer)      -> execute deploy.php via verify-toolkit.php
#
# System packages are only installed when missing (Cloud Agent builds boot from a
# snapshot that already contains them), so re-runs converge quickly.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RUNTIME_DIR="${REPO_ROOT}/.cursor/horizon-deploy-runtime"
PHP_VERSION="8.4"

log() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }

ensure_php() {
    if command -v php >/dev/null 2>&1; then
        return
    fi
    log "Installing PHP ${PHP_VERSION} CLI + extensions"
    export DEBIAN_FRONTEND=noninteractive
    sudo apt-get update -qq
    sudo apt-get install -y -qq software-properties-common ca-certificates curl unzip
    sudo add-apt-repository -y ppa:ondrej/php
    sudo apt-get update -qq
    sudo apt-get install -y -qq \
        "php${PHP_VERSION}-cli" "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml" \
        "php${PHP_VERSION}-curl" "php${PHP_VERSION}-zip" "php${PHP_VERSION}-intl" \
        "php${PHP_VERSION}-bcmath"
}

ensure_composer() {
    if command -v composer >/dev/null 2>&1; then
        return
    fi
    log "Installing Composer"
    local expected actual
    expected="$(curl -fsSL https://composer.github.io/installer.sig)"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    actual="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
    if [ "$expected" != "$actual" ]; then
        echo "Composer installer checksum mismatch" >&2
        rm -f /tmp/composer-setup.php
        exit 1
    fi
    sudo php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
}

ensure_apt_pkg() { # <package> <binary>
    local pkg="$1" bin="$2"
    if command -v "$bin" >/dev/null 2>&1; then
        return
    fi
    log "Installing ${pkg}"
    export DEBIAN_FRONTEND=noninteractive
    sudo apt-get update -qq
    sudo apt-get install -y -qq "$pkg"
}

ensure_actionlint() {
    if command -v actionlint >/dev/null 2>&1; then
        return
    fi
    log "Installing actionlint"
    local tmp
    tmp="$(mktemp -d)"
    ( cd "$tmp" && curl -fsSL https://raw.githubusercontent.com/rhysd/actionlint/main/scripts/download-actionlint.bash | bash >/dev/null )
    sudo mv "$tmp/actionlint" /usr/local/bin/actionlint
    sudo chmod +x /usr/local/bin/actionlint
    rm -rf "$tmp"
}

provision_runtime() {
    log "Provisioning horizon-deploy toolkit runtime (composer)"
    if [ -f "${RUNTIME_DIR}/composer.lock" ]; then
        composer install --no-interaction --no-progress --working-dir="${RUNTIME_DIR}"
    else
        composer update --no-interaction --no-progress --working-dir="${RUNTIME_DIR}"
    fi
}

ensure_php
ensure_composer
ensure_apt_pkg shellcheck shellcheck
ensure_apt_pkg yamllint yamllint
ensure_actionlint
provision_runtime

log "Toolchain versions"
php -v | head -1
composer --version
printf 'shellcheck %s\n' "$(shellcheck --version | awk '/version:/{print $2}')"
printf 'yamllint %s\n' "$(yamllint --version | awk '{print $2}')"
printf 'actionlint %s\n' "$(actionlint --version | head -1)"

log "Smoke test: horizon-deploy toolkit"
php "${REPO_ROOT}/.cursor/verify-toolkit.php"

log "Setup complete"
