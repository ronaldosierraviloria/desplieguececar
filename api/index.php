<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// 1. Serve static files directly if requested
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && file_exists(__DIR__ . '/../public' . $uri)) {
    return false;
}

define('LARAVEL_START', microtime(true));

// 2. Prepare writable /tmp/storage directories for Vercel serverless environment
$tmpStorage = '/tmp/storage';
$directories = [
    $tmpStorage . '/framework/views',
    $tmpStorage . '/framework/cache/data',
    $tmpStorage . '/framework/sessions',
    $tmpStorage . '/framework/bootstrap/cache',
    $tmpStorage . '/logs',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// 3. Set environment variables for storage overrides
putenv('VIEW_COMPILED_PATH=' . $tmpStorage . '/framework/views');
$_ENV['VIEW_COMPILED_PATH'] = $tmpStorage . '/framework/views';

putenv('APP_SERVICES_CACHE=' . $tmpStorage . '/framework/bootstrap/cache/services.php');
$_ENV['APP_SERVICES_CACHE'] = $tmpStorage . '/framework/bootstrap/cache/services.php';

putenv('APP_PACKAGES_CACHE=' . $tmpStorage . '/framework/bootstrap/cache/packages.php');
$_ENV['APP_PACKAGES_CACHE'] = $tmpStorage . '/framework/bootstrap/cache/packages.php';

putenv('APP_CONFIG_CACHE=' . $tmpStorage . '/framework/bootstrap/cache/config.php');
$_ENV['APP_CONFIG_CACHE'] = $tmpStorage . '/framework/bootstrap/cache/config.php';

putenv('APP_ROUTES_CACHE=' . $tmpStorage . '/framework/bootstrap/cache/routes.php');
$_ENV['APP_ROUTES_CACHE'] = $tmpStorage . '/framework/bootstrap/cache/routes.php';

// 4. Register the Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// 5. Bootstrap Laravel
/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

// 6. Bind writable /tmp storage path to application
$app->useStoragePath($tmpStorage);

// 7. Handle Request
$app->handleRequest(Request::capture());
