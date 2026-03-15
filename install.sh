#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  Untitled CMS — Interactive Installer
#  Usage: bash install.sh
# ─────────────────────────────────────────────────────────────────────────────
set -e

# Colors
RED='\033[0;31m'
YEL='\033[1;33m'
GRN='\033[0;32m'
BLU='\033[0;34m'
CYN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

PASS="${GRN}✔${NC}"
FAIL="${RED}✖${NC}"
WARN="${YEL}⚠${NC}"
INFO="${BLU}→${NC}"

separator() { echo -e "${CYN}──────────────────────────────────────────────────${NC}"; }
header()    { echo -e "\n${BOLD}${BLU}$1${NC}"; separator; }

# ─── Welcome ──────────────────────────────────────────────────────────────────
clear
echo -e "${BOLD}"
echo "  ██╗   ██╗███╗   ██╗████████╗██╗████████╗██╗     ███████╗██████╗ "
echo "  ██║   ██║████╗  ██║╚══██╔══╝██║╚══██╔══╝██║     ██╔════╝██╔══██╗"
echo "  ██║   ██║██╔██╗ ██║   ██║   ██║   ██║   ██║     █████╗  ██║  ██║"
echo "  ██║   ██║██║╚██╗██║   ██║   ██║   ██║   ██║     ██╔══╝  ██║  ██║"
echo "  ╚██████╔╝██║ ╚████║   ██║   ██║   ██║   ███████╗███████╗██████╔╝ "
echo "   ╚═════╝ ╚═╝  ╚═══╝   ╚═╝   ╚═╝   ╚═╝   ╚══════╝╚══════╝╚═════╝  "
echo "                ██████╗███╗   ███╗███████╗"
echo "               ██╔════╝████╗ ████║██╔════╝"
echo "               ██║     ██╔████╔██║███████╗"
echo "               ██║     ██║╚██╔╝██║╚════██║"
echo "               ╚██████╗██║ ╚═╝ ██║███████║"
echo "                ╚═════╝╚═╝     ╚═╝╚══════╝"
echo -e "  ${NC}${CYN}AI-native CMS · Laravel 12 · MongoDB · React + Inertia.js${NC}"
echo ""
echo -e "  This script will check your environment and set up the project."
echo -e "  It will NOT overwrite an existing ${BOLD}.env${NC} file."
echo ""
separator

ERRORS=0

# ─── Prerequisite Checks ──────────────────────────────────────────────────────
header "Checking Prerequisites"

# PHP
if command -v php &>/dev/null; then
    PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    PHP_MAJOR=$(php -r "echo PHP_MAJOR_VERSION;")
    PHP_MINOR=$(php -r "echo PHP_MINOR_VERSION;")
    if [ "$PHP_MAJOR" -gt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -ge 2 ]); then
        echo -e " $PASS PHP $PHP_VER"
    else
        echo -e " $FAIL PHP $PHP_VER — ${RED}requires PHP >= 8.2${NC}"
        ERRORS=$((ERRORS+1))
    fi
else
    echo -e " $FAIL PHP not found — ${RED}install PHP 8.2+${NC}  https://www.php.net/downloads"
    ERRORS=$((ERRORS+1))
fi

# Required PHP extensions
for EXT in mongodb mbstring xml curl zip gd fileinfo; do
    if php -m 2>/dev/null | grep -qi "^$EXT$"; then
        echo -e " $PASS PHP ext-$EXT"
    else
        echo -e " $WARN PHP ext-$EXT not found — ${YEL}may be required at runtime${NC}"
    fi
done

# Composer
if command -v composer &>/dev/null; then
    COMPOSER_VER=$(composer --version --no-ansi 2>&1 | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
    echo -e " $PASS Composer $COMPOSER_VER"
else
    echo -e " $FAIL Composer not found — ${RED}install from https://getcomposer.org${NC}"
    ERRORS=$((ERRORS+1))
fi

# Node.js
if command -v node &>/dev/null; then
    NODE_VER=$(node --version | sed 's/v//')
    NODE_MAJOR=$(echo "$NODE_VER" | cut -d. -f1)
    if [ "$NODE_MAJOR" -ge 18 ]; then
        echo -e " $PASS Node.js v$NODE_VER"
    else
        echo -e " $FAIL Node.js v$NODE_VER — ${RED}requires Node.js v18+${NC}  https://nodejs.org"
        ERRORS=$((ERRORS+1))
    fi
else
    echo -e " $FAIL Node.js not found — ${RED}install v18+ from https://nodejs.org${NC}"
    ERRORS=$((ERRORS+1))
fi

# npm
if command -v npm &>/dev/null; then
    NPM_VER=$(npm --version)
    echo -e " $PASS npm $NPM_VER"
else
    echo -e " $FAIL npm not found — ${RED}bundled with Node.js, re-install it${NC}"
    ERRORS=$((ERRORS+1))
fi

# MongoDB
MONGO_FOUND=false
if command -v mongod &>/dev/null; then
    MONGO_VER=$(mongod --version 2>&1 | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
    echo -e " $PASS MongoDB $MONGO_VER (local)"
    MONGO_FOUND=true
elif command -v mongosh &>/dev/null; then
    echo -e " $PASS mongosh found (assuming MongoDB is available)"
    MONGO_FOUND=true
else
    echo -e " $WARN MongoDB CLI not found locally."
    echo -e "       You can use ${BOLD}MongoDB Atlas${NC} (free cloud cluster) instead."
    echo -e "       → https://www.mongodb.com/atlas/database"
fi

if [ "$ERRORS" -gt 0 ]; then
    echo ""
    echo -e "${RED}${BOLD}$ERRORS prerequisite(s) failed. Please fix them before continuing.${NC}"
    exit 1
fi

echo ""
echo -e " ${GRN}${BOLD}All critical prerequisites satisfied.${NC}"

# ─── .env Setup ───────────────────────────────────────────────────────────────
header "Environment Configuration"

if [ -f ".env" ]; then
    echo -e " $INFO ${BOLD}.env${NC} already exists — skipping copy."
    echo -e "      To reconfigure, delete ${BOLD}.env${NC} and re-run this script."
else
    cp .env.example .env
    echo -e " $PASS Copied ${BOLD}.env.example${NC} → ${BOLD}.env${NC}"

    echo ""
    echo -e " ${BOLD}Configure your environment:${NC} (press Enter to keep defaults)"
    echo ""

    # APP_URL
    read -rp "  App URL [http://localhost:8000]: " INPUT_URL
    APP_URL="${INPUT_URL:-http://localhost:8000}"
    sed -i.bak "s|APP_URL=.*|APP_URL=${APP_URL}|" .env

    # DB connection type
    echo ""
    echo -e "  ${BOLD}Database setup:${NC}"
    echo "  [1] Local MongoDB (default)"
    echo "  [2] MongoDB Atlas (cloud)"
    echo "  [3] SQLite (testing only — limited functionality)"
    read -rp "  Choice [1]: " DB_CHOICE
    DB_CHOICE="${DB_CHOICE:-1}"

    case "$DB_CHOICE" in
        2)
            echo ""
            read -rp "  MongoDB Atlas URI (mongodb+srv://...): " ATLAS_URI
            if [ -n "$ATLAS_URI" ]; then
                # Remove individual host/port keys, insert URI
                sed -i.bak '/^DB_HOST=/d' .env
                sed -i.bak '/^DB_PORT=/d' .env
                sed -i.bak '/^DB_DATABASE=/d' .env
                sed -i.bak "s|DB_CONNECTION=.*|DB_CONNECTION=mongodb\nDB_URI=${ATLAS_URI}|" .env
                echo -e " $PASS Atlas URI configured."
            fi
            ;;
        3)
            sed -i.bak "s|DB_CONNECTION=.*|DB_CONNECTION=sqlite|" .env
            sed -i.bak '/^DB_HOST=/d' .env
            sed -i.bak '/^DB_PORT=/d' .env
            sed -i.bak '/^DB_DATABASE=untitled_cms/d' .env
            touch database/database.sqlite 2>/dev/null || true
            echo -e " $WARN SQLite configured. Some features (AI Hub, Vault) require MongoDB."
            ;;
        *)
            read -rp "  MongoDB host [127.0.0.1]: " DB_HOST
            DB_HOST="${DB_HOST:-127.0.0.1}"
            read -rp "  MongoDB port [27017]: " DB_PORT
            DB_PORT="${DB_PORT:-27017}"
            read -rp "  Database name [untitled_cms]: " DB_NAME
            DB_NAME="${DB_NAME:-untitled_cms}"
            read -rp "  MongoDB username (leave blank if auth disabled): " DB_USERNAME
            read -rsp "  MongoDB password (leave blank if auth disabled): " DB_PASSWORD
            echo ""
            sed -i.bak "s|DB_HOST=.*|DB_HOST=${DB_HOST}|" .env
            sed -i.bak "s|DB_PORT=.*|DB_PORT=${DB_PORT}|" .env
            sed -i.bak "s|DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
            if [ -n "$DB_USERNAME" ]; then
                sed -i.bak "s|^#\{0,1\} *DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME}|" .env
            fi
            if [ -n "$DB_PASSWORD" ]; then
                sed -i.bak "s|^#\{0,1\} *DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env
            fi
            echo -e " $PASS Local MongoDB configured."
            ;;
    esac

    # Clean up sed backup files (macOS creates .bak files)
    rm -f .env.bak

    echo ""
    echo -e " ${YEL}Other optional configuration (skip for now, configure in Admin → Settings later):${NC}"
    echo -e "  • Social login (Google / GitHub): add OAuth credentials to ${BOLD}.env${NC}"
    echo -e "  • Email: set ${BOLD}MAIL_MAILER${NC} (currently logs to storage/logs/laravel.log)"
    echo -e "  • AI providers: configured in Admin → AI Hubs after setup"
fi

# ─── Install Dependencies ─────────────────────────────────────────────────────
header "Installing PHP Dependencies"
composer install --no-interaction --prefer-dist

header "Installing Node.js Dependencies"
npm install

# ─── Application Key ──────────────────────────────────────────────────────────
header "Generating Application Key"
php artisan key:generate --ansi

# ─── Storage Permissions & Link ───────────────────────────────────────────────
header "Setting Up Storage"
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
php artisan storage:link --ansi

# ─── Database ─────────────────────────────────────────────────────────────────
header "Running Migrations"
php artisan migrate --force --ansi

header "Seeding Database"
echo -e " $INFO Creating roles, admin user, default settings, AI providers, and sample content..."
php artisan db:seed --force --ansi

# ─── Build Frontend Assets ────────────────────────────────────────────────────
header "Building Frontend Assets"
npm run build

# ─── Cache Clear ──────────────────────────────────────────────────────────────
header "Clearing Caches"
php artisan optimize:clear --ansi

# ─── Done ─────────────────────────────────────────────────────────────────────
separator
echo ""
echo -e "${BOLD}${GRN}  Installation complete!${NC}"
echo ""
echo -e "${BOLD}  Default login credentials:${NC}"
echo -e "  ${INFO} URL:      ${APP_URL:-http://localhost:8000}/login"
echo -e "  ${INFO} Email:    admin@example.com"
echo -e "  ${INFO} Password: password"
echo ""
echo -e "${YEL}  ⚠  Change the admin password immediately after first login!${NC}"
echo ""
echo -e "${BOLD}  Start the development server:${NC}"
echo -e "  ${INFO} ${BLU}composer run dev${NC}   (server + queue + logs + Vite HMR)"
echo ""
separator
