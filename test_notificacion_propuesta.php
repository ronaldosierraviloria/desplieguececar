<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Trabajo;
use App\Models\Estudiante;
use App\Models\Director;
use App\Mail\PropuestaSubidaNotificacion;
use Illuminate\Support\Facades\Mail;

echo "============================================================\n";
echo "    TEST NOTIFICACION POR CORREO - PROPUESTA DE GRADO\n";
echo "============================================================\n\n";

$trabajo = new Trabajo([
    'codigo_proyecto' => 'PGTG-001-26',
    'titulo' => 'Sistema de Gestión de Trabajos de Grado con Integración Serverless',
    'fecha_subida' => now()->toDateString(),
    'plantilla_rubrica' => 'propuesta_de_grado',
    'estado' => 'subido',
]);

echo "Proyecto de prueba creado:\n";
echo "  Código:    " . $trabajo->codigo_proyecto . "\n";
echo "  Título:    " . $trabajo->titulo . "\n\n";

echo "Intentando enviar correo de prueba a sierraviloria10@gmail.com...\n";

try {
    Mail::to('sierraviloria10@gmail.com')->send(new PropuestaSubidaNotificacion(
        $trabajo,
        'Ronaldo Sierra Viloria',
        'Estudiante'
    ));

    echo "✅ Correo enviado con éxito vía Brevo SMTP!\n";
    echo "   Revisa la bandeja de entrada de sierraviloria10@gmail.com\n";

} catch (\Throwable $e) {
    echo "❌ Error al enviar el correo:\n";
    echo "   " . $e->getMessage() . "\n";
}
