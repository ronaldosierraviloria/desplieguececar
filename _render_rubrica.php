<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Evaluacion;
use App\Models\Profesor;
use App\Models\Usuario;

// Autenticar como el evaluador 4 (evaluadorprueba2@sistema.com)
Auth::loginUsingId(4);

// Crear mock evaluacion para pruebas de la vista
$trabajo = new \App\Models\Trabajo([
    'id_trabajo' => 1,
    'codigo_proyecto' => 'PGTG-001-26',
    'titulo' => 'Modelo de prediccion del puntaje de las pruebas icfes en los estudiantes de 11 grado',
    'plantilla_rubrica' => 'trabajo_de_grado'
]);
$trabajo->setRelation('estudiante', collect([
    new \App\Models\Usuario(['nombre' => 'Ronaldo', 'apellido' => 'Sierra Vitoria'])
]));
$trabajo->setRelation('directores', collect([
    new \App\Models\Usuario(['nombre' => 'Ronaldo', 'apellido' => 'Viloria']),
    new \App\Models\Usuario(['nombre' => 'Carlos', 'apellido' => 'Ejemplo'])
]));

$evaluacion = new Evaluacion([
    'id_evaluacion' => 1,
    'tipo_plantilla' => 'trabajo_de_grado',
    'nota_final' => 4.3,
    'resultado' => 'aceptada',
    'observaciones_globales' => "Este es un comentario de prueba para verificar la adaptabilidad del campo de observaciones adicionales.\nSegunda línea de observaciones con recomendaciones para el estudiante.",
    'criterios' => [
        ['id' => 1, 'calificacion' => 4.0, 'comentario' => 'Buen título.'],
        ['id' => 2, 'calificacion' => 5.0, 'comentario' => 'Excelente justificación.'],
        ['id' => 3, 'calificacion' => 4.0, 'comentario' => 'Objetivo general claro.'],
        ['id' => 4, 'calificacion' => 3.0, 'comentario' => 'Ajustar objetivos específicos.'],
        ['id' => 5, 'calificacion' => 5.0, 'comentario' => 'Marco referencial completo.'],
        ['id' => 6, 'calificacion' => 5.0, 'comentario' => 'Metodología clara.'],
        ['id' => 7, 'calificacion' => 3.0, 'comentario' => 'Actualizar bibliografía.']
    ]
]);
$evaluacion->setRelation('trabajo', $trabajo);


$usuario    = Usuario::find(4);
$evaluador1 = Profesor::with('usuario')->find(2);
$evaluador2 = Profesor::with('usuario')->find(3);

echo 'Trabajo: ' . $evaluacion->trabajo->titulo . PHP_EOL;
echo 'Tipo: ' . $evaluacion->tipo_plantilla . PHP_EOL;
echo 'Estudiantes: ' . $evaluacion->trabajo->estudiante->count() . PHP_EOL;
echo 'Directores: ' . $evaluacion->trabajo->directores->count() . PHP_EOL;
echo 'Criterios en data: ' . count($evaluacion->criterios ?? []) . PHP_EOL;

$html = view('evaluador.rubrica_pdf', compact('usuario', 'evaluacion', 'evaluador1', 'evaluador2'))->render();
file_put_contents(__DIR__ . '/_rubrica_test.html', $html);
echo 'HTML guardado: ' . strlen($html) . ' bytes' . PHP_EOL;

// Verificaciones estructurales
echo 'Marcadores salto-pagina: ' . substr_count($html, 'salto-pagina') . PHP_EOL;
echo 'Cajas resultado: ' . substr_count($html, 'resultado-card') . PHP_EOL;
echo 'Seccion firmas: ' . (str_contains($html, 'firma-container') ? 'OK' : 'FALTA') . PHP_EOL;
echo 'Tabla rubrica: ' . (str_contains($html, 'rubrica-table') ? 'OK' : 'FALTA') . PHP_EOL;
echo 'Cabecera: ' . (str_contains($html, 'EVALUACIÓN DE LA PROPUESTA DE TRABAJO DE GRADO') ? 'OK' : 'FALTA') . PHP_EOL;

