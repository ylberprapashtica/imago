#!/bin/bash
composer install --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
npm install
npm run build 