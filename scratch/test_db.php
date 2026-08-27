<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;

$trabajo = App\Models\Trabajo::find(1);
if ($trabajo) {
    echo "DB archivo_pdf: " . $trabajo->archivo_pdf . "\n";
    $relative = preg_replace('#^storage/#', '', $trabajo->archivo_pdf);
    echo "Relative path: " . $relative . "\n";
    echo "Storage::disk('public')->exists(\$relative): " . (Storage::disk('public')->exists($relative) ? 'TRUE' : 'FALSE') . "\n";
    echo "Storage::disk('public')->path(\$relative): " . Storage::disk('public')->path($relative) . "\n";
    echo "file_exists(Storage path): " . (file_exists(Storage::disk('public')->path($relative)) ? 'TRUE' : 'FALSE') . "\n";
    echo "file_exists(public_path): " . (file_exists(public_path($trabajo->archivo_pdf)) ? 'TRUE' : 'FALSE') . "\n";
    echo "file_exists(base_path): " . (file_exists(base_path($trabajo->archivo_pdf)) ? 'TRUE' : 'FALSE') . "\n";
} else {
    echo "No trabajo found.\n";
}
