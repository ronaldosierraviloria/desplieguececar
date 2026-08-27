<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            '/logout',
        ]);
        $middleware->alias([
            'check.activo' => \App\Http\Middleware\CheckActivo::class,
            'check.role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

// Override storage path for serverless environments (Vercel)
if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || (function_exists('getenv') && getenv('VERCEL'))) {
    $tmpStorage = '/tmp/storage';
    foreach ([
        $tmpStorage . '/framework/views',
        $tmpStorage . '/framework/cache/data',
        $tmpStorage . '/framework/sessions',
        $tmpStorage . '/framework/bootstrap/cache',
        $tmpStorage . '/logs',
    ] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
    $app->useStoragePath($tmpStorage);
}

return $app;
