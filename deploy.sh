#!/usr/bin/env bash
# AMSAAS Bluehost Deployment Script
# Target: amsaas.rasulmart.com
# Run this from your LOCAL machine: bash deploy.sh
set -e

# ─── Config ────────────────────────────────────────────────────────────────────
SSH_USER="rasulmar"
SSH_HOST="rasulmart.com"
SSH_PASS="ALIsax@123456"
DEPLOY_DIR="/home/rasulmar/public_html/amsaas"
SUBDOMAIN="amsaas.rasulmart.com"
REPO_URL="https://github.com/alimoallim/amsaas.git"
BRANCH="claude/ecstatic-allen-0cd0qy"

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=rasulmar_laravel
DB_USERNAME=rasulmar_amsaas
DB_PASSWORD="ALIsax@123456"

APP_KEY="$(openssl rand -base64 32)"

# ─── Helper ────────────────────────────────────────────────────────────────────
ssh_run() {
    sshpass -p "$SSH_PASS" ssh -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" "$@"
}

echo "==> Connecting to server..."
ssh_run "echo 'SSH OK'; php -v | head -1; composer --version 2>/dev/null || echo 'no composer'; node -v 2>/dev/null || echo 'no node'"

echo ""
echo "==> Setting up deployment directory..."
ssh_run "mkdir -p ${DEPLOY_DIR}"

echo ""
echo "==> Cloning/updating repository..."
ssh_run "
  if [ -d '${DEPLOY_DIR}/.git' ]; then
    cd ${DEPLOY_DIR} && git fetch origin && git checkout ${BRANCH} && git pull origin ${BRANCH}
  else
    git clone --branch ${BRANCH} ${REPO_URL} ${DEPLOY_DIR}
  fi
"

echo ""
echo "==> Writing .env file..."
ssh_run "cat > ${DEPLOY_DIR}/laravel/.env << 'ENVEOF'
APP_NAME=AMSAAS
APP_ENV=production
APP_KEY=base64:${APP_KEY}
APP_DEBUG=false
APP_URL=https://${SUBDOMAIN}

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=${DB_CONNECTION}
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

SANCTUM_STATEFUL_DOMAINS=${SUBDOMAIN}
SESSION_DOMAIN=.rasulmart.com

MAIL_MAILER=log

EVC_WEBHOOK_SECRET=change_me_in_production
SMS_DRIVER=log
ENVEOF"

echo ""
echo "==> Installing PHP dependencies..."
ssh_run "cd ${DEPLOY_DIR}/laravel && composer install --no-dev --optimize-autoloader --no-interaction"

echo ""
echo "==> Running migrations..."
ssh_run "cd ${DEPLOY_DIR}/laravel && php artisan migrate --force"

echo ""
echo "==> Caching config/routes..."
ssh_run "cd ${DEPLOY_DIR}/laravel && php artisan config:cache && php artisan route:cache && php artisan view:cache"

echo ""
echo "==> Setting permissions..."
ssh_run "
  chmod -R 755 ${DEPLOY_DIR}/laravel
  chmod -R 775 ${DEPLOY_DIR}/laravel/storage ${DEPLOY_DIR}/laravel/bootstrap/cache
"

echo ""
echo "==> Building frontend..."
ssh_run "
  if command -v node &>/dev/null; then
    cd ${DEPLOY_DIR}/frontend && npm ci && npm run build
    echo 'Frontend built successfully'
  else
    echo 'WARNING: node not found - build frontend locally and upload dist/ manually'
  fi
"

echo ""
echo "==> Configuring subdomain (Nginx/Apache)..."
# Check what web server is running
WEB_SERVER=$(ssh_run "ps aux | grep -E 'nginx|apache|httpd' | grep -v grep | head -1 | awk '{print \$11}'")
echo "Web server: $WEB_SERVER"

if echo "$WEB_SERVER" | grep -q "nginx"; then
  ssh_run "
    sudo tee /etc/nginx/conf.d/${SUBDOMAIN}.conf > /dev/null << 'NGINXEOF'
server {
    listen 80;
    server_name ${SUBDOMAIN};
    root ${DEPLOY_DIR}/laravel/public;
    index index.php;

    add_header X-Frame-Options SAMEORIGIN;
    add_header X-Content-Type-Options nosniff;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
NGINXEOF
    sudo nginx -t && sudo systemctl reload nginx
  "
else
  echo "Apache detected or unknown - creating .htaccess based config"
  ssh_run "
    # Create symlink so amsaas.rasulmart.com document root maps to Laravel public/
    mkdir -p /home/rasulmar/public_html/amsaas_public
    ln -sfn ${DEPLOY_DIR}/laravel/public /home/rasulmar/public_html/amsaas_public
    echo 'NOTE: Configure the subdomain in cPanel to point to: ${DEPLOY_DIR}/laravel/public'
  "
fi

echo ""
echo "==> Deployment complete!"
echo ""
echo "NEXT STEPS:"
echo "1. In Bluehost cPanel > Subdomains, create 'amsaas' pointing to:"
echo "   ${DEPLOY_DIR}/laravel/public"
echo ""
echo "2. If Node.js is not on server, build frontend locally:"
echo "   cd frontend && npm ci && npm run build"
echo "   Then upload frontend/dist/ to ${DEPLOY_DIR}/frontend/dist/"
echo ""
echo "3. Enable HTTPS in cPanel > SSL/TLS > Let's Encrypt"
echo ""
echo "4. Visit https://${SUBDOMAIN} to verify deployment"
