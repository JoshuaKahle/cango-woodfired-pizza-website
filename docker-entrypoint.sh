#!/bin/sh
set -e

# The app folder is bind-mounted from ./public_html -> /var/www/html
# Ensure Composer dependencies are installed for Dompdf.
if [ -f /var/www/html/composer.json ] && [ ! -f /var/www/html/vendor/autoload.php ]; then
  echo "[entrypoint] vendor/autoload.php missing; running composer install..." >&2
  composer install --no-interaction --no-progress --prefer-dist --working-dir=/var/www/html
fi

exec "$@"
