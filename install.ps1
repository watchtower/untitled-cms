<#
.SYNOPSIS
    Untitled CMS - Interactive Installer for Windows
.DESCRIPTION
    Sets up the project environment on Windows systems.
    Requires PowerShell 5.1+ and checks all prerequisites before installation.
#>
$ErrorActionPreference = "Stop"

# Helper: run a native command and abort on failure
function Invoke-NativeCommand {
    param([string]$Description, [string]$Command, [string[]]$Arguments)
    & $Command @Arguments
    if ($LASTEXITCODE -ne 0) {
        Write-Host "`n✖ $Description failed (exit code $LASTEXITCODE)." -ForegroundColor Red
        exit 1
    }
}

# Setup basic UI elements
$Host.UI.RawUI.WindowTitle = "Untitled CMS Installer"
Clear-Host

Write-Host ""
Write-Host "  ██╗   ██╗███╗   ██╗████████╗██╗████████╗██╗     ███████╗██████╗ " -ForegroundColor DarkGray
Write-Host "  ██║   ██║████╗  ██║╚══██╔══╝██║╚══██╔══╝██║     ██╔════╝██╔══██╗" -ForegroundColor DarkGray
Write-Host "  ██║   ██║██╔██╗ ██║   ██║   ██║   ██║   ██║     █████╗  ██║  ██║" -ForegroundColor DarkGray
Write-Host "  ██║   ██║██║╚██╗██║   ██║   ██║   ██║   ██║     ██╔══╝  ██║  ██║" -ForegroundColor DarkGray
Write-Host "  ╚██████╔╝██║ ╚████║   ██║   ██║   ██║   ███████╗███████╗██████╔╝ " -ForegroundColor DarkGray
Write-Host "   ╚═════╝ ╚═╝  ╚═══╝   ╚═╝   ╚═╝   ╚═╝   ╚══════╝╚══════╝╚═════╝  " -ForegroundColor DarkGray
Write-Host "                ██████╗███╗   ███╗███████╗" -ForegroundColor DarkGray
Write-Host "               ██╔════╝████╗ ████║██╔════╝" -ForegroundColor DarkGray
Write-Host "               ██║     ██╔████╔██║███████╗" -ForegroundColor DarkGray
Write-Host "               ██║     ██║╚██╔╝██║╚════██║" -ForegroundColor DarkGray
Write-Host "               ╚██████╗██║ ╚═╝ ██║███████║" -ForegroundColor DarkGray
Write-Host "                ╚═════╝╚═╝     ╚═╝╚══════╝" -ForegroundColor DarkGray
Write-Host "  AI-native CMS · Laravel 13 · MongoDB · React + Inertia.js" -ForegroundColor Cyan
Write-Host ""
Write-Host "  This script will check your environment and set up the project."
Write-Host "  It will NOT overwrite an existing .env file."
Write-Host ""

function Print-Header($text) {
    Write-Host "`n──────────────────────────────────────────────────" -ForegroundColor Cyan
    Write-Host $text -ForegroundColor Blue
    Write-Host "──────────────────────────────────────────────────" -ForegroundColor Cyan
}

$Errors = 0
$PhpFound = $false

# ─── Prerequisite Checks ──────────────────────────────────────────────────────
Print-Header "Checking Prerequisites"

# PHP
if (Get-Command php -ErrorAction SilentlyContinue) {
    $PhpFound = $true
    $PHP_VER = php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;"
    $PHP_MAJOR = php -r "echo PHP_MAJOR_VERSION;"
    $PHP_MINOR = php -r "echo PHP_MINOR_VERSION;"

    if (([int]$PHP_MAJOR -gt 8) -or (([int]$PHP_MAJOR -eq 8) -and ([int]$PHP_MINOR -ge 2))) {
        Write-Host " ✔ PHP $PHP_VER" -ForegroundColor Green
    } else {
        Write-Host " ✖ PHP $PHP_VER — requires PHP >= 8.2" -ForegroundColor Red
        $Errors++
    }
} else {
    Write-Host " ✖ PHP not found — install PHP 8.2+ from https://windows.php.net/download/" -ForegroundColor Red
    $Errors++
}

# Required PHP extensions (only check if PHP is installed)
if ($PhpFound) {
    $RequiredExtensions = @("mongodb", "mbstring", "xml", "curl", "zip", "gd", "fileinfo")
    $InstalledExtensions = php -m 2>$null
    foreach ($Ext in $RequiredExtensions) {
        if ($InstalledExtensions -match "(?i)^$Ext$") {
            Write-Host " ✔ PHP ext-$Ext" -ForegroundColor Green
        } else {
            Write-Host " ⚠ PHP ext-$Ext not found — may be required at runtime" -ForegroundColor Yellow
        }
    }
}

# Composer
if (Get-Command composer -ErrorAction SilentlyContinue) {
    $COMPOSER_OUT = composer --version --no-ansi 2>&1 | Select-String -Pattern '[0-9]+\.[0-9]+\.[0-9]+'
    if ($COMPOSER_OUT) {
        $COMPOSER_VER = $COMPOSER_OUT.Matches.Value | Select-Object -First 1
        Write-Host " ✔ Composer $COMPOSER_VER" -ForegroundColor Green
    } else {
        Write-Host " ✔ Composer found" -ForegroundColor Green
    }
} else {
    Write-Host " ✖ Composer not found — install from https://getcomposer.org" -ForegroundColor Red
    $Errors++
}

# Node.js
if (Get-Command node -ErrorAction SilentlyContinue) {
    $NODE_VER = (node --version) -replace 'v', ''
    $NODE_MAJOR = [int]$NODE_VER.Split('.')[0]
    $NODE_MINOR = [int]$NODE_VER.Split('.')[1]
    if ($NODE_MAJOR -gt 22 -or ($NODE_MAJOR -eq 22 -and $NODE_MINOR -ge 12)) {
        Write-Host " ✔ Node.js v$NODE_VER" -ForegroundColor Green
    } else {
        Write-Host " ✖ Node.js v$NODE_VER — requires Node.js v22.12+ https://nodejs.org" -ForegroundColor Red
        $Errors++
    }
} else {
    Write-Host " ✖ Node.js not found — install v22.12+ from https://nodejs.org" -ForegroundColor Red
    $Errors++
}

# npm
if (Get-Command npm -ErrorAction SilentlyContinue) {
    $NPM_VER = npm --version
    Write-Host " ✔ npm $NPM_VER" -ForegroundColor Green
} else {
    Write-Host " ✖ npm not found — bundled with Node.js, re-install it" -ForegroundColor Red
    $Errors++
}

# MongoDB
if (Get-Command mongod -ErrorAction SilentlyContinue) {
    Write-Host " ✔ MongoDB daemon (mongod) found locally" -ForegroundColor Green
} elseif (Get-Command mongosh -ErrorAction SilentlyContinue) {
    Write-Host " ✔ mongosh found (assuming MongoDB is available)" -ForegroundColor Green
} else {
    Write-Host " ⚠ MongoDB CLI not found locally." -ForegroundColor Yellow
    Write-Host "       You can use MongoDB Atlas (free cloud cluster) instead." -ForegroundColor Yellow
    Write-Host "       → https://www.mongodb.com/atlas/database" -ForegroundColor Yellow
}

if ($Errors -gt 0) {
    Write-Host "`n$Errors prerequisite(s) failed. Please fix them before continuing." -ForegroundColor Red
    exit 1
}

Write-Host "`n All critical prerequisites satisfied." -ForegroundColor Green

# ─── .env Setup ───────────────────────────────────────────────────────────────
Print-Header "Environment Configuration"

# Track APP_URL for the final message (default if .env already exists)
$AppUrl = "http://localhost:8000"

if (Test-Path ".env") {
    Write-Host " → .env already exists — skipping copy." -ForegroundColor Cyan
    Write-Host "      To reconfigure, delete .env and re-run this script."
} else {
    Copy-Item ".env.example" -Destination ".env"
    Write-Host " ✔ Copied .env.example → .env" -ForegroundColor Green

    Write-Host "`n Configure your environment: (press Enter to keep defaults)`n"

    # APP_URL
    $INPUT_URL = Read-Host "  App URL [http://localhost:8000]"
    if ([string]::IsNullOrWhiteSpace($INPUT_URL)) { $INPUT_URL = "http://localhost:8000" }
    $AppUrl = $INPUT_URL
    (Get-Content .env) -replace "^APP_URL=.*", "APP_URL=$INPUT_URL" | Set-Content .env

    # DB connection type
    Write-Host "`n  Database setup:"
    Write-Host "  [1] Local MongoDB (default)"
    Write-Host "  [2] MongoDB Atlas (cloud)"
    Write-Host "  [3] SQLite (testing only — limited functionality)"
    $DB_CHOICE = Read-Host "  Choice [1]"
    if ([string]::IsNullOrWhiteSpace($DB_CHOICE)) { $DB_CHOICE = "1" }

    switch ($DB_CHOICE) {
        "2" {
            Write-Host ""
            $ATLAS_URI = Read-Host "  MongoDB Atlas URI (mongodb+srv://...)"
            if (-not [string]::IsNullOrWhiteSpace($ATLAS_URI)) {
                $envFile = Get-Content .env | Where-Object { $_ -notmatch "^DB_HOST=" -and $_ -notmatch "^DB_PORT=" -and $_ -notmatch "^DB_DATABASE=" }
                $envFile -replace "^DB_CONNECTION=.*", "DB_CONNECTION=mongodb`nDB_URI=$ATLAS_URI" | Set-Content .env
                Write-Host " ✔ Atlas URI configured." -ForegroundColor Green
            }
        }
        "3" {
            $envFile = Get-Content .env | Where-Object { $_ -notmatch "^DB_HOST=" -and $_ -notmatch "^DB_PORT=" -and $_ -notmatch "^DB_DATABASE=untitled_cms" }
            $envFile -replace "^DB_CONNECTION=.*", "DB_CONNECTION=sqlite" | Set-Content .env
            if (-not (Test-Path "database/database.sqlite")) { New-Item -Path "database" -Name "database.sqlite" -ItemType "File" -ErrorAction SilentlyContinue | Out-Null }
            Write-Host " ⚠ SQLite configured. Some features (AI Hub, Vault) require MongoDB." -ForegroundColor Yellow
        }
        default {
            $DB_HOST = Read-Host "  MongoDB host [127.0.0.1]"
            if ([string]::IsNullOrWhiteSpace($DB_HOST)) { $DB_HOST = "127.0.0.1" }

            $DB_PORT = Read-Host "  MongoDB port [27017]"
            if ([string]::IsNullOrWhiteSpace($DB_PORT)) { $DB_PORT = "27017" }

            $DB_NAME = Read-Host "  Database name [untitled_cms]"
            if ([string]::IsNullOrWhiteSpace($DB_NAME)) { $DB_NAME = "untitled_cms" }

            $DB_USERNAME = Read-Host "  MongoDB username (leave blank if auth disabled)"
            $DB_PASSWORD_SECURE = Read-Host "  MongoDB password (leave blank if auth disabled)" -AsSecureString
            $DB_PASS_PLAIN = ""
            if ($DB_PASSWORD_SECURE.Length -gt 0) {
                $DB_PASS_PLAIN = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto([System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($DB_PASSWORD_SECURE))
            }

            Write-Host ""
            $envContent = Get-Content .env
            $envContent = $envContent -replace "^DB_HOST=.*", "DB_HOST=$DB_HOST"
            $envContent = $envContent -replace "^DB_PORT=.*", "DB_PORT=$DB_PORT"
            $envContent = $envContent -replace "^DB_DATABASE=.*", "DB_DATABASE=$DB_NAME"

            if (-not [string]::IsNullOrWhiteSpace($DB_USERNAME)) {
                $envContent = $envContent -replace "^#?\s*DB_USERNAME=.*", "DB_USERNAME=$DB_USERNAME"
            }
            if (-not [string]::IsNullOrWhiteSpace($DB_PASS_PLAIN)) {
                $envContent = $envContent -replace "^#?\s*DB_PASSWORD=.*", "DB_PASSWORD=$DB_PASS_PLAIN"
            }

            $envContent | Set-Content .env
            Write-Host " ✔ Local MongoDB configured." -ForegroundColor Green
        }
    }

    Write-Host "`n Other optional configuration (skip for now, configure in Admin → Settings later):" -ForegroundColor Yellow
    Write-Host "  • Social login (Google / GitHub): add OAuth credentials to .env"
    Write-Host "  • Email: set MAIL_MAILER (currently logs to storage/logs/laravel.log)"
    Write-Host "  • AI providers: configured in Admin → AI Hubs after setup"
}

# ─── Install Dependencies ─────────────────────────────────────────────────────
Print-Header "Installing PHP Dependencies"
Invoke-NativeCommand -Description "Composer install" -Command "composer" -Arguments @("install", "--no-interaction", "--prefer-dist")

Print-Header "Installing Node.js Dependencies"
Invoke-NativeCommand -Description "npm install" -Command "npm" -Arguments @("install")

# ─── Application Key ──────────────────────────────────────────────────────────
Print-Header "Generating Application Key"
Invoke-NativeCommand -Description "Key generation" -Command "php" -Arguments @("artisan", "key:generate", "--ansi")

# ─── Storage Link ─────────────────────────────────────────────────────────────
Print-Header "Setting Up Storage Link"
# chmod is not needed on Windows
Invoke-NativeCommand -Description "Storage link" -Command "php" -Arguments @("artisan", "storage:link", "--ansi")

# ─── Database ─────────────────────────────────────────────────────────────────
Print-Header "Running Migrations"
Invoke-NativeCommand -Description "Database migration" -Command "php" -Arguments @("artisan", "migrate", "--force", "--ansi")

Print-Header "Seeding Database"
Write-Host " → Creating roles, admin user, default settings, AI providers, and sample content..." -ForegroundColor Cyan
Invoke-NativeCommand -Description "Database seeding" -Command "php" -Arguments @("artisan", "db:seed", "--force", "--ansi")

# ─── Build Frontend Assets ────────────────────────────────────────────────────
Print-Header "Building Frontend Assets"
Invoke-NativeCommand -Description "Frontend build" -Command "npm" -Arguments @("run", "build")

# ─── Cache Clear ──────────────────────────────────────────────────────────────
Print-Header "Clearing Caches"
Invoke-NativeCommand -Description "Cache clear" -Command "php" -Arguments @("artisan", "optimize:clear", "--ansi")

# ─── Done ─────────────────────────────────────────────────────────────────────
Write-Host "`n──────────────────────────────────────────────────" -ForegroundColor Cyan
Write-Host "`n  Installation complete!`n" -ForegroundColor Green
Write-Host "  Default login credentials:"
Write-Host "  → URL:      $AppUrl/login" -ForegroundColor Cyan
Write-Host "  → Email:    admin@example.com" -ForegroundColor Cyan
Write-Host "  → Password: password" -ForegroundColor Cyan
Write-Host "`n  ⚠ Change the admin password immediately after first login!" -ForegroundColor Yellow
Write-Host "`n  Start the development server:"
Write-Host "  → composer run dev   (server + queue + logs + Vite HMR)`n" -ForegroundColor Cyan
Write-Host "──────────────────────────────────────────────────" -ForegroundColor Cyan
