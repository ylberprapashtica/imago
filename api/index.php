<?php

// Initialize Laravel at runtime
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Generate application key if not exists
if (!file_exists(__DIR__ . '/../.env')) {
    copy(__DIR__ . '/../.env.example', __DIR__ . '/../.env');
    $app->make('artisan')->call('key:generate');
}

// Optimize Laravel
$app->make('artisan')->call('config:cache');
$app->make('artisan')->call('route:cache');
$app->make('artisan')->call('view:cache');

// Create storage link if it doesn't exist
if (!file_exists(__DIR__ . '/../public/storage')) {
    $app->make('artisan')->call('storage:link');
}

// Forward the request to Laravel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$kernel->terminate($request, $response);
$response->send(); 