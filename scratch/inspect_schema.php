<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "============================================================\n";
echo "    INSPECCION DE COLUMNAS DE LA TABLA TRABAJO_PROFESOR\n";
echo "============================================================\n\n";

$columnas = Schema::getColumnListing('trabajo_profesor');
echo "Columnas encontradas en trabajo_profesor:\n";
print_r($columnas);

echo "\nTipos de columna en trabajo_profesor:\n";
foreach ($columnas as $col) {
    $type = DB::getSchemaBuilder()->getColumnType('trabajo_profesor', $col);
    echo "  - {$col}: {$type}\n";
}
