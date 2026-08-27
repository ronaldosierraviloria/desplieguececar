<?php

// Forward Vercel requests to normal index.php

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && file_exists(__DIR__ . '/../public' . $uri)) {
    return false;
}

// Prepare storage subdirectories in /tmp for Vercel serverless environment
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

// Override compiled storage paths for serverless environment
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

require __DIR__ . '/../public/index.php';
