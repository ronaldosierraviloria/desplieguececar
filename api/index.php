<?php

// 1. Enable full error diagnostics for debugging Vercel environment
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo '<div style="font-family: sans-serif; padding: 20px; background: #fff3f3; color: #900; border: 1px solid #fcc; border-radius: 8px; margin: 20px;">';
        echo '<h2>PHP Fatal Shutdown Error</h2>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($error['message']) . '</p>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($error['file']) . ' (Line ' . $error['line'] . ')</p>';
        echo '</div>';
    }
});

// 2. Serve static files directly if requested
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && file_exists(__DIR__ . '/../public' . $uri)) {
    return false;
}

// 3. Prepare writable /tmp/storage directories for Vercel serverless environment
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

// 4. Override compiled storage paths for serverless environment
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

// 5. Handover to public/index.php
require __DIR__ . '/../public/index.php';
