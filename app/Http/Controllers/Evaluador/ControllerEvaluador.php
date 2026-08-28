<?php

namespace App\Http\Controllers\Evaluador;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Profesor;
use App\Models\Trabajo;
use App\Models\Evaluacion;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\PropuestaCalificadaEstudianteMailable;
use App\Mail\PropuestaNuevaVersionRequeridaGestorMailable;
use App\Mail\ReevaluacionFinalizadaEstudianteMailable;
use App\Mail\ReevaluacionFinalizadaGestorMailable;
use App\Mail\PropuestaAprobadaGestorMailable;
use App\Mail\TrabajoFinalSegundoInformeGestorMailable;
use App\Mail\TrabajoFinalAprobadoAdminMailable;
use App\Mail\RubricaFinalPDFEstudianteMailable;
use App\Notifications\PropuestaEvaluada;
use App\Notifications\TrabajoRechazado;
use App\Notifications\TrabajoAceptado;
use OpenApi\Attributes as OA;



/** Controlador para las acciones del evaluador (revisión, aceptación/rechazo de trabajos). */
class ControllerEvaluador extends Controller
{
    /**
     * Muestra el dashboard con los trabajos de grado asignados al evaluador logueado.
     * Esta función reemplaza al método index() que estaba en EvaluadorController.
     */
    public function index()
    {
        $usuario = Auth::user();
        
        // 1. Verificación de Rol y Obtención de ID del Profesor
        if (!$usuario->profesor) {
            return redirect('/')->with('error', 'Tu cuenta no está vinculada correctamente. Contacta al administrador.');
        }

        $profesorId = $usuario->profesor->id_profesor;

        // 2. Carga de Trabajos Asignados
        // Usamos la relación 'trabajos()' definida en Profesor.php
        $evaluador = Profesor::with([
            'trabajos' => function ($query) {
                // Seleccionamos los datos del pivote para mostrar estado y fechas límite
                $query->withPivot('fecha_asignacion', 'fecha_limite_revision', 'estado_revision', 'decision_evaluador', 'motivo_rechazo', 'terminos_aceptados', 'datos_aceptados', 'requiere_nueva_revision')
                      ->orderBy('trabajo.id_trabajo', 'desc');
            },
            'trabajos.estudiante', // Cargamos los datos de los estudiantes
            'trabajos.tipo',       // Cargamos el tipo de trabajo
            'trabajos.evaluadores', // Necesario para verificar si el otro jurado ya finalizó
            'trabajos.evaluaciones', // Cargar evaluaciones para mostrar nota y resultado
            'trabajos.directores'  // Cargar directores
        ])->findOrFail($profesorId);

        $trabajosAsignados = $evaluador->trabajos->filter(function ($trabajo) {
            $miEvaluacion = $trabajo->evaluadores->where('id_profesor', auth()->user()->profesor->id_profesor)->first();
            $miRevisionFinalizada = $miEvaluacion && $miEvaluacion->pivot->estado_revision === 'Finalizado';
            $requiereNuevaRevision = $miEvaluacion && $miEvaluacion->pivot->requiere_nueva_revision;

            // Mostrar en el dashboard si mi revisión no está finalizada o si requiere nueva revisión
            return !$miRevisionFinalizada || $requiereNuevaRevision;
        })->values();

        // 3. Devuelve la vista (evaluador.dashboard)
        // La interfaz seguirá leyéndola porque recibe las mismas variables ($usuario, $trabajosAsignados).
        return view('evaluador.dashboard', compact('usuario', 'trabajosAsignados'));
    }

    public function trabajosCalificados()
    {
        $usuario = Auth::user();
        if (!$usuario->profesor) {
            return redirect('/')->with('error', 'Tu cuenta no está vinculada correctamente. Contacta al administrador.');
        }

        $profesorId = $usuario->profesor->id_profesor;

        $evaluador = Profesor::with([
            'trabajos' => function ($query) {
                $query->withPivot('fecha_asignacion', 'fecha_limite_revision', 'estado_revision', 'decision_evaluador', 'motivo_rechazo', 'terminos_aceptados', 'datos_aceptados', 'requiere_nueva_revision');
            },
            'trabajos.estudiante',
            'trabajos.tipo',
            'trabajos.evaluadores',
            'trabajos.evaluaciones',
            'trabajos.directores'
        ])->findOrFail($profesorId);

        $trabajosCalificados = $evaluador->trabajos->filter(function ($trabajo) {
            $miEvaluacion = $trabajo->evaluadores->where('id_profesor', auth()->user()->profesor->id_profesor)->first();
            $miRevisionFinalizada = $miEvaluacion && $miEvaluacion->pivot->estado_revision === 'Finalizado';
            $requiereNuevaRevision = $miEvaluacion && $miEvaluacion->pivot->requiere_nueva_revision;

            // Mostrar en calificados si mi revisión está finalizada y NO requiere nueva revisión
            return $miRevisionFinalizada && !$requiereNuevaRevision;
        })->values();

        return view('evaluador.calificados', compact('usuario', 'trabajosCalificados'));
    }
    #[OA\Get(
        path: '/trabajos/{id}/rubrica',
        summary: 'Obtener rúbrica de un trabajo',
        tags: ['Evaluador'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Criterios de la rúbrica', content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'criterio', type: 'string'),
                new OA\Property(property: 'puntaje_max', type: 'number'),
                new OA\Property(property: 'calificacion', type: 'number', nullable: true),
                new OA\Property(property: 'comentario', type: 'string'),
            ]))),
            new OA\Response(response: 404, description: 'Trabajo no encontrado'),
        ]
    )]
    public function getRubrica($id_trabajo)
    {
    $trabajo = Trabajo::findOrFail($id_trabajo);
    $filePath = storage_path('app/rubricas/' . $trabajo->archivo_rubrica);

    $phpWord = IOFactory::load($filePath);
    $criterios = [];

    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            if (method_exists($element, 'getRows')) {
                foreach ($element->getRows() as $row) {
                    $cells = $row->getCells();
                    if(count($cells) >= 2){
                        $criterios[] = [
                            'criterio' => $cells[0]->getText(),
                            'puntaje_max' => floatval($cells[1]->getText()),
                            'calificacion' => null,
                            'comentario' => ''
                        ];
                    }
                }
            }
        }
    }

    return response()->json($criterios);
    }   
    /**
     * Determinar el slot del evaluador (1 o 2) según el orden de asignación
     */
    private function getEvaluadorSlot($id_trabajo, $profesorId): int
    {
        $evaluadores = DB::table('trabajo_profesor')
            ->where('id_trabajo', $id_trabajo)
            ->orderBy('fecha_asignacion', 'asc')
            ->orderBy('id_profesor', 'asc')
            ->pluck('id_profesor')
            ->toArray();

        $posicion = array_search($profesorId, $evaluadores);
        return ($posicion !== false) ? $posicion + 1 : 1;
    }

    #[OA\Post(
        path: '/trabajos/{id}/guardar-evaluacion',
        summary: 'Guardar evaluación completa de un trabajo',
        tags: ['Evaluador'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'tipo_plantilla', type: 'string', enum: ['propuesta_de_grado', 'pasantia', 'trabajo_de_grado']),
                    new OA\Property(property: 'nota_final', type: 'number', nullable: true),
                    new OA\Property(property: 'resultado', type: 'string'),
                    new OA\Property(property: 'observaciones_globales', type: 'string', nullable: true),
                    new OA\Property(property: 'criterios', type: 'array', items: new OA\Items(properties: [
                        new OA\Property(property: 'id', type: 'integer', nullable: true),
                        new OA\Property(property: 'descripcion', type: 'string', nullable: true),
                        new OA\Property(property: 'calificacion', type: 'number', nullable: true),
                        new OA\Property(property: 'comentario', type: 'string', nullable: true),
                        new OA\Property(property: 'valoracion', type: 'string', enum: ['excelente', 'aceptable', 'deficiente'], nullable: true),
                    ])),
                    new OA\Property(property: 'firma', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Evaluación guardada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean'),
                new OA\Property(property: 'nota_final', type: 'number'),
                new OA\Property(property: 'resultado', type: 'string'),
                new OA\Property(property: 'evaluacion_completada', type: 'boolean'),
            ])),
            new OA\Response(response: 400, description: 'Evaluador no encontrado'),
            new OA\Response(response: 403, description: 'Debe aceptar el trabajo primero'),
            new OA\Response(response: 404, description: 'Trabajo no encontrado'),
        ]
    )]
    public function guardarEvaluacion(Request $request, $id_trabajo)
{
    $trabajo = Trabajo::findOrFail($id_trabajo);

    $data = $request->validate([
        'tipo_plantilla' => 'required|in:propuesta_de_grado,pasantia,trabajo_de_grado',
        'nota_final' => 'nullable|numeric|min:0|max:5',
        'resultado' => 'required|string|max:50',
        'observaciones_globales' => 'nullable|string',
        'criterios' => 'nullable|array',
        'criterios.*.id' => 'nullable|integer',
        'criterios.*.descripcion' => 'nullable|string',
        'criterios.*.calificacion' => 'nullable|numeric|min:0|max:5',
        'criterios.*.comentario' => 'nullable|string',
        'criterios.*.valoracion' => 'nullable|string|in:excelente,aceptable,deficiente',
        'firma' => 'nullable|string',
    ]);

    $usuario = Auth::user();
    if (!$usuario->profesor) {
        return response()->json(['success' => false, 'message' => 'Evaluador no encontrado.'], 400);
    }

    $profesorId = $usuario->profesor->id_profesor;

    // Verificar que el evaluador haya aceptado el trabajo
    $miDecision = DB::table('trabajo_profesor')
        ->where('id_trabajo', $id_trabajo)
        ->where('id_profesor', $profesorId)
        ->value('decision_evaluador');

    if ($miDecision !== 'aceptado') {
        return response()->json(['success' => false, 'message' => 'Debe aceptar el trabajo antes de evaluarlo.'], 403);
    }

    // Determinar slot del lado del servidor (seguro) - ignoramos el que envía el cliente
    $slot = $this->getEvaluadorSlot($trabajo->id_trabajo, $profesorId);

    // Buscar evaluación existente por id_trabajo y id_profesor (evaluación independiente)
    $evaluacion = Evaluacion::where('id_trabajo', $trabajo->id_trabajo)
        ->where('id_profesor', $profesorId)
        ->first();

    if (!$evaluacion) {
        $evaluacion = new Evaluacion();
        $evaluacion->id_trabajo = $trabajo->id_trabajo;
        $evaluacion->id_profesor = $profesorId;
    }

    // Si la evaluación anterior estaba completada (nuevo ciclo o corrección)
    // limpiar firma y datos del ciclo anterior
    if ($evaluacion->evaluacion_completada) {
        $evaluacion->firma = null;
        $evaluacion->evaluacion_completada = false;
    }

    $evaluacion->tipo_plantilla = $data['tipo_plantilla'];
    $evaluacion->nota_final = $data['nota_final'];
    $evaluacion->resultado = $data['resultado'];
    
    // Guardar observaciones separadas por tipo
    $obsPorTipo = $evaluacion->observaciones_por_tipo ?? [];
    $obsPorTipo[$data['tipo_plantilla']] = $data['observaciones_globales'] ?? null;
    $evaluacion->observaciones_por_tipo = $obsPorTipo;
    $evaluacion->observaciones_globales = $data['observaciones_globales'];
    $evaluacion->criterios = $data['criterios'] ?? [];

    // Guardar firma
    $firmaValida = !empty($data['firma']) && strlen($data['firma']) > 100;
    if ($firmaValida) {
        $evaluacion->firma = $data['firma'];
    }

    // Marcar estado_revision como Finalizado en la pivot y limpiar requiere_nueva_revision
    DB::table('trabajo_profesor')
        ->where('id_trabajo', $trabajo->id_trabajo)
        ->where('id_profesor', $profesorId)
        ->update([
            'estado_revision' => 'Finalizado',
            'requiere_nueva_revision' => false
        ]);

    // La evaluación actual está completada si tiene firma
    $evaluacion->evaluacion_completada = !empty($evaluacion->firma);
    $evaluacion->save();

    // Verificar si AMBOS evaluadores finalizaron (para historial y notificaciones)
    $totalEvaluadores = DB::table('trabajo_profesor')
        ->where('id_trabajo', $trabajo->id_trabajo)
        ->count();
    $finalizados = DB::table('trabajo_profesor')
        ->where('id_trabajo', $trabajo->id_trabajo)
        ->where('estado_revision', 'Finalizado')
        ->count();
    $ambosFinalizados = $totalEvaluadores > 0 && $finalizados >= $totalEvaluadores;

    // Registrar en el historial de estados
    $tipoLabel = match($data['tipo_plantilla']) {
        'propuesta_de_grado' => 'Propuesta',
        'trabajo_de_grado' => 'Trabajo de Grado',
        'pasantia' => 'Pasantía',
        default => 'Documento'
    };
    \App\Models\HistorialEstado::create([
        'trabajo_grado_id' => $trabajo->id_trabajo,
        'estado' => $ambosFinalizados ? 'evaluacion_completada' : 'evaluado',
        'user_id' => $usuario->id_usuario,
        'observacion_estado' => "{$tipoLabel} evaluada por {$usuario->nombre} {$usuario->apellido}. Resultado: {$data['resultado']} (Nota: {$data['nota_final']})",
    ]);

    if ($ambosFinalizados) {
        try {
            $gestores = Usuario::where('rol', 'Gestor')->where('activo', true)->get();
            foreach ($gestores as $gestor) {
                $gestor->notify(new PropuestaEvaluada($trabajo));
            }
        } catch (\Throwable $e) {
            \Log::error('Error al notificar gestores: ' . $e->getMessage());
        }
    }

    // ── GESTIÓN DE NOTIFICACIONES POR CORREO SEGÚN EL FLUJO DE EVALUACIÓN ──
    try {
        $trabajo->load(['estudiante', 'directores', 'evaluadores.usuario', 'evaluaciones']);
        $notaPromedio = $trabajo->nota_promedio;
        $resultadoFinal = $data['resultado'];
        $obs = $data['observaciones_globales'] ?? '';

        // Renderizar vista de rúbrica para adjuntar como contenido HTML/PDF
        $pdfHtml = '';
        try {
            $pdfHtml = view('evaluador.rubrica_pdf', compact('usuario', 'evaluacion'))->render();
        } catch (\Throwable $te) {
            \Log::warning('No se pudo renderizar la rúbrica PDF para correo: ' . $te->getMessage());
        }

        $resLower = strtolower(trim($resultadoFinal));

        // 1. PROPUESTA DE GRADO
        if ($data['tipo_plantilla'] === 'propuesta_de_grado') {
            // Notificar a estudiantes cuando ambos evaluadores han finalizado (o evaluador único)
            if ($ambosFinalizados || $totalEvaluadores <= 1) {
                foreach ($trabajo->estudiante as $est) {
                    if (!empty($est->correo)) {
                        Mail::to($est->correo)->send(new PropuestaCalificadaEstudianteMailable(
                            $trabajo,
                            trim($est->nombre . ' ' . $est->apellido),
                            $notaPromedio,
                            $resultadoFinal,
                            $obs,
                            route('evaluador.rubrica-pdf', $trabajo->id_trabajo),
                            $pdfHtml
                        ));
                    }
                }

                // Si requiere un nuevo documento de correcciones
                if (in_array($resLower, ['aceptada_con_mejoras', 'rechazada', 'requiere_correcciones'])) {
                    $gestores = Usuario::where('rol', 'Gestor')->where('activo', true)->get();
                    foreach ($gestores as $gestor) {
                        if (!empty($gestor->correo)) {
                            Mail::to($gestor->correo)->send(new PropuestaNuevaVersionRequeridaGestorMailable(
                                $trabajo,
                                trim($gestor->nombre . ' ' . $gestor->apellido),
                                $resultadoFinal
                            ));
                        }
                    }
                } else {
                    // Propuesta Aprobada / Óptima -> Notificar a Gestor que estudiantes enviarán Informe Final
                    $gestores = Usuario::where('rol', 'Gestor')->where('activo', true)->get();
                    foreach ($gestores as $gestor) {
                        if (!empty($gestor->correo)) {
                            Mail::to($gestor->correo)->send(new PropuestaAprobadaGestorMailable(
                                $trabajo,
                                trim($gestor->nombre . ' ' . $gestor->apellido)
                            ));
                        }
                    }
                }
            }

            // Notificar sobre la finalización de re-evaluación si aplica
            if ($trabajo->estado === 'version_corregida_subida') {
                foreach ($trabajo->estudiante as $est) {
                    if (!empty($est->correo)) {
                        Mail::to($est->correo)->send(new ReevaluacionFinalizadaEstudianteMailable(
                            $trabajo,
                            trim($est->nombre . ' ' . $est->apellido),
                            $resultadoFinal
                        ));
                    }
                }
                $gestores = Usuario::where('rol', 'Gestor')->where('activo', true)->get();
                foreach ($gestores as $gestor) {
                    if (!empty($gestor->correo)) {
                        Mail::to($gestor->correo)->send(new ReevaluacionFinalizadaGestorMailable(
                            $trabajo,
                            trim($gestor->nombre . ' ' . $gestor->apellido),
                            $resultadoFinal
                        ));
                    }
                }
            }
        } 
        // 2. INFORME FINAL DE TRABAJO DE GRADO / PASANTÍA
        else {
            if ($ambosFinalizados || $totalEvaluadores <= 1) {
                // Enviar Rúbrica Descargable PDF en Informe Final a Estudiantes
                foreach ($trabajo->estudiante as $est) {
                    if (!empty($est->correo)) {
                        Mail::to($est->correo)->send(new RubricaFinalPDFEstudianteMailable(
                            $trabajo,
                            trim($est->nombre . ' ' . $est->apellido),
                            $notaPromedio ?? $data['nota_final'],
                            $resultadoFinal,
                            $obs,
                            $pdfHtml
                        ));
                    }
                }

                // Si se necesita un segundo informe / corrección en trabajo final
                if (in_array($resLower, ['aceptada_con_mejoras', 'requiere_segundo_informe', 'requiere_correcciones'])) {
                    $gestores = Usuario::where('rol', 'Gestor')->where('activo', true)->get();
                    foreach ($gestores as $gestor) {
                        if (!empty($gestor->correo)) {
                            Mail::to($gestor->correo)->send(new TrabajoFinalSegundoInformeGestorMailable(
                                $trabajo,
                                trim($gestor->nombre . ' ' . $gestor->apellido),
                                'Gestor'
                            ));
                        }
                    }
                    foreach ($trabajo->evaluadores as $evaluador) {
                        if ($evaluador->usuario && !empty($evaluador->usuario->correo)) {
                            Mail::to($evaluador->usuario->correo)->send(new TrabajoFinalSegundoInformeGestorMailable(
                                $trabajo,
                                trim($evaluador->usuario->nombre . ' ' . $evaluador->usuario->apellido),
                                'Evaluador'
                            ));
                        }
                    }
                } else {
                    // Resultado satisfactorio (Aprobado) -> Notificar al Admin de habilitación espacio Acta de Sustentación
                    $admins = Usuario::where('rol', 'Administrador')->where('activo', true)->get();
                    foreach ($admins as $admin) {
                        if (!empty($admin->correo)) {
                            Mail::to($admin->correo)->send(new TrabajoFinalAprobadoAdminMailable(
                                $trabajo,
                                trim($admin->nombre . ' ' . $admin->apellido)
                            ));
                        }
                    }
                }
            }
        }
    } catch (\Throwable $e) {
        \Log::error('Error enviando notificaciones por correo tras evaluación: ' . $e->getMessage());
    }

    return response()->json([
        'success' => true,
        'nota_final' => $data['nota_final'],
        'resultado' => $data['resultado'],
        'evaluacion_completada' => $ambosFinalizados,
    ]);
}

#[OA\Post(
    path: '/trabajos/{id}/guardar-progreso',
    summary: 'Guardar progreso parcial de evaluación',
    tags: ['Evaluador'],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'tipo_plantilla', type: 'string', enum: ['propuesta_de_grado', 'pasantia', 'trabajo_de_grado']),
                new OA\Property(property: 'nota_final', type: 'number', nullable: true),
                new OA\Property(property: 'resultado', type: 'string', nullable: true),
                new OA\Property(property: 'observaciones_globales', type: 'string', nullable: true),
                new OA\Property(property: 'criterios', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'id', type: 'integer', nullable: true),
                    new OA\Property(property: 'descripcion', type: 'string', nullable: true),
                    new OA\Property(property: 'calificacion', type: 'number', nullable: true),
                    new OA\Property(property: 'comentario', type: 'string', nullable: true),
                    new OA\Property(property: 'valoracion', type: 'string', enum: ['excelente', 'aceptable', 'deficiente'], nullable: true),
                ])),
                new OA\Property(property: 'firma', type: 'string', nullable: true),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Progreso guardado', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean'),
            new OA\Property(property: 'message', type: 'string'),
        ])),
        new OA\Response(response: 400, description: 'Evaluador no encontrado'),
        new OA\Response(response: 403, description: 'Debe aceptar el trabajo primero'),
        new OA\Response(response: 404, description: 'Trabajo no encontrado'),
    ]
)]
public function guardarProgreso(Request $request, $id_trabajo)
{
    $trabajo = Trabajo::findOrFail($id_trabajo);

    $data = $request->validate([
        'tipo_plantilla' => 'required|in:propuesta_de_grado,pasantia,trabajo_de_grado',
        'nota_final' => 'nullable|numeric|min:0|max:5',
        'resultado' => 'nullable|string|max:50',
        'observaciones_globales' => 'nullable|string',
        'criterios' => 'nullable|array',
        'criterios.*.id' => 'nullable|integer',
        'criterios.*.descripcion' => 'nullable|string',
        'criterios.*.calificacion' => 'nullable|numeric|min:0|max:5',
        'criterios.*.comentario' => 'nullable|string',
        'criterios.*.valoracion' => 'nullable|string|in:excelente,aceptable,deficiente',
        'firma' => 'nullable|string',
    ]);

    $usuario = Auth::user();
    if (!$usuario->profesor) {
        return response()->json(['success' => false, 'message' => 'Evaluador no encontrado.'], 400);
    }

    $profesorId = $usuario->profesor->id_profesor;

    // Verificar que el evaluador haya aceptado el trabajo
    $miDecision = DB::table('trabajo_profesor')
        ->where('id_trabajo', $id_trabajo)
        ->where('id_profesor', $profesorId)
        ->value('decision_evaluador');

    if ($miDecision !== 'aceptado') {
        return response()->json(['success' => false, 'message' => 'Debe aceptar el trabajo antes de guardar progreso.'], 403);
    }

    // Buscar evaluación existente por id_trabajo y id_profesor (evaluación independiente)
    $evaluacion = Evaluacion::where('id_trabajo', $trabajo->id_trabajo)
        ->where('id_profesor', $profesorId)
        ->first();

    if (!$evaluacion) {
        $evaluacion = new Evaluacion();
        $evaluacion->id_trabajo = $trabajo->id_trabajo;
        $evaluacion->id_profesor = $profesorId;
    }

    // Actualizar campos compartidos (sin cambiar estado_revision)
    $evaluacion->tipo_plantilla = $data['tipo_plantilla'];
    $evaluacion->nota_final = $data['nota_final'] ?? null;
    $evaluacion->resultado = $data['resultado'] ?? null;
    
    // Guardar observaciones separadas por tipo
    $obsPorTipo = $evaluacion->observaciones_por_tipo ?? [];
    $obsPorTipo[$data['tipo_plantilla']] = $data['observaciones_globales'] ?? null;
    $evaluacion->observaciones_por_tipo = $obsPorTipo;
    $evaluacion->observaciones_globales = $data['observaciones_globales'] ?? null;
    $evaluacion->criterios = $data['criterios'] ?? [];

    // Persistir la firma
    $firmaValida = !empty($data['firma']) && strlen($data['firma']) > 100;
    if ($firmaValida) {
        $evaluacion->firma = $data['firma'];
    }

    $evaluacion->save();

    return response()->json(['success' => true, 'message' => 'Progreso guardado correctamente.']);
}

#[OA\Post(
    path: '/evaluador/aceptar-terminos',
    summary: 'Aceptar términos y condiciones para evaluar un trabajo',
    tags: ['Evaluador'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'trabajo_id', type: 'integer', description: 'ID del trabajo'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Términos aceptados', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean'),
        ])),
        new OA\Response(response: 400, description: 'Evaluador no encontrado o ID requerido'),
    ]
)]
public function aceptarTerminos(Request $request)
{
    try {
        $usuario = Auth::user();
        if (!$usuario) {
            return response()->json(['success' => false, 'message' => 'Evaluador no autenticado.'], 401);
        }

        $profesor = $usuario->profesor;
        if (!$profesor) {
            $profesor = Profesor::where('id_usuario', $usuario->id_usuario)->first();
        }

        if (!$profesor) {
            return response()->json(['success' => false, 'message' => 'Evaluador no encontrado para este usuario.'], 400);
        }

        $trabajoId = $request->input('trabajo_id');
        if (!$trabajoId) {
            return response()->json(['success' => false, 'message' => 'ID de trabajo requerido.'], 400);
        }

        DB::table('trabajo_profesor')
            ->where('id_trabajo', $trabajoId)
            ->where('id_profesor', $profesor->id_profesor)
            ->update([
                'decision_evaluador' => 'aceptado',
                'terminos_aceptados' => true,
                'datos_aceptados' => true,
            ]);

        return response()->json(['success' => true]);
    } catch (\Throwable $e) {
        \Log::error('Error en aceptarTerminos: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
        return response()->json([
            'success' => false,
            'message' => 'Error al procesar la aceptación de términos.',
            'error_detail' => $e->getMessage()
        ], 200);
    }
}

public function detallesEvaluacion($id)
{
    $usuario = Auth::user();
    if (!$usuario || !$usuario->profesor) {
        return redirect('/')->with('error', 'Tu cuenta no está vinculada correctamente.');
    }

    $profesorId = $usuario->profesor->id_profesor;

    // 1. Buscar por ID directo de la evaluación
    $evaluacion = Evaluacion::with([
        'trabajo.tipo', 
        'trabajo.estudiante', 
        'trabajo.directores', 
        'trabajo.evaluadores.usuario', 
        'profesor.usuario'
    ])->find($id);

    // 2. Si no coincide con un ID de evaluación, buscar por id_trabajo e id_profesor
    if (!$evaluacion) {
        $evaluacion = Evaluacion::where('id_trabajo', $id)
            ->where('id_profesor', $profesorId)
            ->with(['trabajo.tipo', 'trabajo.estudiante', 'trabajo.directores', 'trabajo.evaluadores.usuario', 'profesor.usuario'])
            ->first();
    }

    // 3. Buscar cualquier evaluación asociada a ese trabajo
    if (!$evaluacion) {
        $evaluacion = Evaluacion::where('id_trabajo', $id)
            ->with(['trabajo.tipo', 'trabajo.estudiante', 'trabajo.directores', 'trabajo.evaluadores.usuario', 'profesor.usuario'])
            ->first();
    }

    if (!$evaluacion) {
        return redirect()->route('evaluador.dashboard')->with('error', 'Evaluación no encontrada.');
    }

    return view('evaluador.detallesEvaluacion', compact('usuario', 'evaluacion'));
}

public function rubricaPDF($id)
{
    $usuario = Auth::user();
    if (!$usuario || !$usuario->profesor) {
        abort(403, 'Acceso no autorizado.');
    }

    $profesorId = $usuario->profesor->id_profesor;

    // 1. Buscar por ID directo de la evaluación
    $evaluacion = Evaluacion::with([
        'trabajo.tipo', 
        'trabajo.estudiante', 
        'trabajo.directores', 
        'trabajo.evaluadores.usuario', 
        'profesor.usuario'
    ])->find($id);

    // 2. Buscar por id_trabajo e id_profesor
    if (!$evaluacion) {
        $evaluacion = Evaluacion::where('id_trabajo', $id)
            ->where('id_profesor', $profesorId)
            ->with(['trabajo.tipo', 'trabajo.estudiante', 'trabajo.directores', 'trabajo.evaluadores.usuario', 'profesor.usuario'])
            ->first();
    }

    // 3. Buscar cualquier evaluación registrada para este trabajo
    if (!$evaluacion) {
        $evaluacion = Evaluacion::where('id_trabajo', $id)
            ->with(['trabajo.tipo', 'trabajo.estudiante', 'trabajo.directores', 'trabajo.evaluadores.usuario', 'profesor.usuario'])
            ->first();
    }

    // 4. Si aún no existe un registro guardado en BD, construir objeto Evaluacion en memoria a partir del Trabajo
    if (!$evaluacion) {
        $trabajo = Trabajo::with(['tipo', 'estudiante', 'directores', 'evaluadores.usuario'])->find($id);
        if (!$trabajo) {
            abort(404, 'Trabajo de grado no encontrado.');
        }

        $evaluacion = new Evaluacion();
        $evaluacion->id_trabajo = $trabajo->id_trabajo;
        $evaluacion->id_profesor = $profesorId;
        $evaluacion->tipo_plantilla = $trabajo->plantilla_rubrica ?? 'propuesta_de_grado';
        $evaluacion->nota_final = null;
        $evaluacion->resultado = '';
        $evaluacion->observaciones_globales = '';
        $evaluacion->criterios = [];
        $evaluacion->setRelation('trabajo', $trabajo);
        $evaluacion->setRelation('profesor', $usuario->profesor);
    }

    return view('evaluador.rubrica_pdf', compact('usuario', 'evaluacion'));
}

public function revisarTrabajo($id)
{
    $usuario = Auth::user();
    if (!$usuario->profesor) {
        abort(403);
    }

    $trabajo = Trabajo::with(['tipo', 'estudiante', 'directores'])
        ->where('id_trabajo', $id)
        ->firstOrFail();

    $trabajo->load('evaluadores.usuario');

    // Obtener datos del pivot del evaluador actual
    $miPivot = DB::table('trabajo_profesor')
        ->where('id_trabajo', $id)
        ->where('id_profesor', $usuario->profesor->id_profesor)
        ->first();

    $miDecision = $miPivot->decision_evaluador ?? null;
    $fechaLimite = $miPivot->fecha_limite_revision ?? null;

    return view('evaluador.revisarTrabajo', compact('usuario', 'trabajo', 'miDecision', 'fechaLimite'));
}

#[OA\Post(
    path: '/evaluador/aceptar/{id}',
    summary: 'Aceptar trabajo asignado para evaluación',
    tags: ['Evaluador'],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Trabajo aceptado', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean'),
            new OA\Property(property: 'message', type: 'string'),
        ])),
        new OA\Response(response: 403, description: 'No autorizado'),
        new OA\Response(response: 404, description: 'Asignación no encontrada'),
    ]
)]
public function aceptarTrabajo($id)
{
    $usuario = Auth::user();
    if (!$usuario) {
        return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
    }
    $profesor = $usuario->profesor ?? Profesor::where('id_usuario', $usuario->id_usuario)->first();
    if (!$profesor) {
        return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
    }

    $updated = DB::table('trabajo_profesor')
        ->where('id_trabajo', $id)
        ->where('id_profesor', $profesor->id_profesor)
        ->update([
            'decision_evaluador' => 'aceptado',
            'terminos_aceptados' => true,
            'datos_aceptados' => true,
        ]);

    if (!$updated) {
        return response()->json(['success' => false, 'message' => 'No se encontró la asignación.'], 404);
    }

    $trabajo = Trabajo::findOrFail($id);
    $nombreEvaluador = $usuario->profesor->nombre . ' ' . $usuario->profesor->apellido;

    // Notificar a gestores y administradores
    $destinatarios = Usuario::where('activo', true)
        ->whereIn('rol', ['Gestor', 'Administrador'])
        ->get();
    foreach ($destinatarios as $destinatario) {
        $url = $destinatario->rol === 'Gestor'
            ? route('gestor.trabajo.detalles', $trabajo->id_trabajo)
            : route('admin.detallesTrabajo', $trabajo->id_trabajo);
        $destinatario->notify(new TrabajoAceptado($trabajo, $nombreEvaluador, $url));
    }

    return response()->json(['success' => true, 'message' => 'Trabajo aceptado.']);
}

#[OA\Post(
    path: '/evaluador/rechazar/{id}',
    summary: 'Rechazar trabajo asignado para evaluación',
    tags: ['Evaluador'],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'motivo', type: 'string', maxLength: 500),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Trabajo rechazado', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean'),
            new OA\Property(property: 'message', type: 'string'),
        ])),
        new OA\Response(response: 403, description: 'No autorizado'),
        new OA\Response(response: 404, description: 'Asignación no encontrada'),
    ]
)]
public function rechazarTrabajo(Request $request, $id)
{
    $usuario = Auth::user();
    if (!$usuario->profesor) {
        return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
    }

    $request->validate([
        'motivo' => 'required|string|max:500',
    ]);

    $updated = DB::table('trabajo_profesor')
        ->where('id_trabajo', $id)
        ->where('id_profesor', $usuario->profesor->id_profesor)
        ->update([
            'decision_evaluador' => 'rechazado',
            'motivo_rechazo' => $request->motivo,
        ]);

    if (!$updated) {
        return response()->json(['success' => false, 'message' => 'No se encontró la asignación.'], 404);
    }

    $trabajo = Trabajo::with('evaluadores')->findOrFail($id);
    $nombreEvaluador = $usuario->profesor->nombre . ' ' . $usuario->profesor->apellido;

    // Registrar en historial de estados
    \App\Models\HistorialEstado::create([
        'trabajo_grado_id' => $trabajo->id_trabajo,
        'estado' => 'evaluador_rechazo',
        'user_id' => $usuario->id_usuario,
        'observacion_estado' => "Evaluador '{$nombreEvaluador}' rechazó evaluar el trabajo. Motivo: {$request->motivo}",
    ]);

    // Notificar a gestores y administradores
    $destinatarios = Usuario::where('activo', true)
        ->whereIn('rol', ['Gestor', 'Administrador'])
        ->get();
    foreach ($destinatarios as $destinatario) {
        $url = $destinatario->rol === 'Gestor'
            ? route('gestor.trabajo.detalles', $trabajo->id_trabajo)
            : route('admin.detallesTrabajo', $trabajo->id_trabajo);
        $destinatario->notify(new TrabajoRechazado($trabajo, $nombreEvaluador, $request->motivo, $url));
    }

    return response()->json(['success' => true, 'message' => 'Trabajo rechazado. Se ha notificado al administrador y gestor.']);
}

}