<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\Trabajo;
use App\Models\Estudiante;
use App\Models\Director;
use App\Models\Usuario;
use App\Services\BusinessDaysService;

// Mailables
use App\Mail\PropuestaSubidaNotificacion;
use App\Mail\EvaluadorAsignadoMailable;
use App\Mail\EstudianteEvaluadoresAsignadosMailable;
use App\Mail\EvaluadorAlertaMailable;
use App\Mail\PropuestaCalificadaEstudianteMailable;
use App\Mail\PropuestaNuevaVersionRequeridaGestorMailable;
use App\Mail\NuevaVersionEstudianteMailable;
use App\Mail\NuevaVersionEvaluadorMailable;
use App\Mail\ReevaluacionFinalizadaEstudianteMailable;
use App\Mail\ReevaluacionFinalizadaGestorMailable;
use App\Mail\PropuestaAprobadaGestorMailable;
use App\Mail\PropuestaConvertidaTGAdminMailable;
use App\Mail\TrabajoFinalEvaluadoresMailable;
use App\Mail\TrabajoFinalSegundoInformeGestorMailable;
use App\Mail\TrabajoFinalAprobadoAdminMailable;
use App\Mail\RubricaFinalPDFEstudianteMailable;

echo "============================================================\n";
echo "   TEST DE VERIFICACION DE LOS 11 TIPOS DE CORREOS DE GRADO\n";
echo "============================================================\n\n";

$targetEmail = 'sierraviloria10@gmail.com';

// Mock de Trabajo
$trabajo = Trabajo::first();
if (!$trabajo) {
    $trabajo = new Trabajo([
        'id_trabajo' => 999,
        'codigo_proyecto' => 'PGTG-001-26',
        'titulo' => 'Proyecto de Prueba de Notificaciones de Grado',
        'fecha_subida' => now()->toDateString(),
        'plantilla_rubrica' => 'propuesta_de_grado',
        'estado' => 'subido',
    ]);
}

$fechaLimite = BusinessDaysService::addBusinessDays(Carbon::now(), 15);
$mockPdfContent = "%PDF-1.4 ... (Contenido Rúbrica de Prueba) ...";

$tests = [
    '1. Propuesta Subida (Estudiantes/Director)' => new PropuestaSubidaNotificacion($trabajo, 'Ronaldo Sierra', 'Estudiante'),
    '2. Evaluadores Asignados (Evaluador)' => new EvaluadorAsignadoMailable($trabajo, 'Prof. Evaluador Test', $fechaLimite, 15),
    '2b. Evaluadores Asignados (Estudiante)' => new EstudianteEvaluadoresAsignadosMailable($trabajo, 'Estudiante Test'),
    '3. Alerta Evaluador (Alerta 5 días)' => new EvaluadorAlertaMailable($trabajo, 'Prof. Evaluador Test', 5, $fechaLimite),
    '4. Propuesta Calificada (Estudiante)' => new PropuestaCalificadaEstudianteMailable($trabajo, 'Estudiante Test', 4.5, 'aceptada', 'Excelente propuesta', 'http://localhost/rubrica.pdf', $mockPdfContent),
    '4b. Propuesta Calificada Nueva Versión Requerida (Gestor)' => new PropuestaNuevaVersionRequeridaGestorMailable($trabajo, 'Gestor Test', 'aceptada_con_mejoras'),
    '5. Nueva Versión Subida (Estudiante)' => new NuevaVersionEstudianteMailable($trabajo, 'Estudiante Test'),
    '5b. Nueva Versión Subida (Evaluador)' => new NuevaVersionEvaluadorMailable($trabajo, 'Prof. Evaluador Test', $fechaLimite, 15),
    '6. Re-evaluación Finalizada (Estudiante)' => new ReevaluacionFinalizadaEstudianteMailable($trabajo, 'Estudiante Test', 'aceptada'),
    '6b. Re-evaluación Finalizada (Gestor)' => new ReevaluacionFinalizadaGestorMailable($trabajo, 'Gestor Test', 'aceptada'),
    '7. Propuesta Aprobada / Óptima (Gestor)' => new PropuestaAprobadaGestorMailable($trabajo, 'Gestor Test'),
    '8. Propuesta Convertida a TG (Admin)' => new PropuestaConvertidaTGAdminMailable($trabajo, 'Admin Test'),
    '9. Evaluación Trabajo Final (Evaluador)' => new TrabajoFinalEvaluadoresMailable($trabajo, 'Prof. Evaluador Test', $fechaLimite, 15),
    '9b. Segundo Informe Trabajo Final (Gestor)' => new TrabajoFinalSegundoInformeGestorMailable($trabajo, 'Gestor Test', 'Gestor'),
    '10. Trabajo Final Aprobado - Acta Sustentación (Admin)' => new TrabajoFinalAprobadoAdminMailable($trabajo, 'Admin Test'),
    '11. Rúbrica Descargable PDF Informe Final (Estudiante)' => new RubricaFinalPDFEstudianteMailable($trabajo, 'Estudiante Test', 4.8, 'aceptada', 'Excelente informe final', $mockPdfContent),
];

$exitosos = 0;
$fallidos = 0;

foreach ($tests as $nombre => $mailable) {
    try {
        // Renders view HTML to check template syntax
        $html = $mailable->render();
        echo "  [OK HTML] {$nombre} - Renderizado: " . strlen($html) . " bytes\n";
        $exitosos++;
    } catch (\Throwable $e) {
        echo "  [ERROR]   {$nombre} - Error: " . $e->getMessage() . "\n";
        $fallidos++;
    }
}

echo "\n============================================================\n";
echo "RESULTADOS DEL TEST DE PLANTILLAS DE CORREO:\n";
echo "  Éxitos:   {$exitosos} / " . count($tests) . "\n";
echo "  Fallos:    {$fallidos}\n";
echo "============================================================\n";
