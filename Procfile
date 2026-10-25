web: php artisan config:cache || true && php artisan route:cache || true && (php artisan migrate --force || echo 'DB migration deferred') && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
