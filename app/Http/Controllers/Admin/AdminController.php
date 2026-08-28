<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Trabajo;
use App\Models\Usuario;
use App\Models\Profesor;
use App\Models\Facultad;
use App\Models\Area;
use App\Models\Estudiante;
use App\Models\Evaluacion;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Services\BusinessDaysService;
use App\Mail\EvaluadorAsignadoMailable;
use App\Mail\EstudianteEvaluadoresAsignadosMailable;
use App\Notifications\EvaluadorAsignado;
use App\Notifications\PlazoExtendido;
use App\Notifications\TrabajoAprobado;
use App\Notifications\TrabajoRetirado;
use App\Notifications\TrabajoReactivado;
use App\Notifications\TrabajoEliminado;
use App\Notifications\TrabajoRetiradoEvaluador;
use App\Notifications\TrabajoEliminadoEvaluador;
use OpenApi\Attributes as OA;

/** Controlador para la gestión administrativa del sistema (dashboard, usuarios, trabajos, asignaciones). */
class AdminController extends Controller
{
     /**
     * Muestra el dashboard principal del administrador.
     */
    public function index(Request $request)
    {
        $usuario = Auth::user();
        $trabajos = Trabajo::with(['estudiante.area.facultad', 'tipo', 'evaluadores', 'evaluaciones'])->orderBy('id_trabajo', 'desc')->get();
        $facultades = Facultad::orderBy('nombre_facultad')->get();
        $areas = Area::orderBy('nombre_area')->get();

        $currentYear = (int) date('Y');
        $startYear = 2026;
        $endYear = max($startYear, $currentYear);
        $yearsDisponibles = range($startYear, $endYear);

        $year = (int) $request->input('year', $endYear);
        if (!in_array($year, $yearsDisponibles)) {
            $year = $endYear;
        }

        // ── 10 KPIs Solicitados ──
        $totalTrabajos = $trabajos->count();
        $sinEvaluadores = $trabajos->filter(fn($t) => $t->evaluadores->count() === 0)->count();
        $conEvaluadores = $totalTrabajos - $sinEvaluadores;

        $finalizados = $trabajos->filter(fn($t) => $t->estado === 'finalizado' || !empty($t->archivo_acta_sustentacion))->count();

        $enRevision = $trabajos->filter(function($t) {
            if ($t->estado === 'finalizado' || !empty($t->archivo_acta_sustentacion)) return false;
            return $t->estado === 'en_revision' || $t->evaluadores->contains(fn($e) => !empty($e->pivot->requiere_nueva_revision));
        })->count();

        $evaluados = $trabajos->filter(function($t) {
            if ($t->estado === 'finalizado' || !empty($t->archivo_acta_sustentacion)) return false;
            if ($t->estado === 'aprobado') return true;
            return $t->evaluadores->count() > 0 && $t->evaluaciones->where('evaluacion_completada', true)->count() >= $t->evaluadores->count();
        })->count();

        $enEvaluacion = $trabajos->filter(function($t) {
            if ($t->evaluadores->count() === 0) return false;
            if ($t->estado === 'finalizado' || !empty($t->archivo_acta_sustentacion)) return false;
            if ($t->estado === 'aprobado') return false;
            $completadas = $t->evaluaciones->where('evaluacion_completada', true)->count();
            return $completadas < $t->evaluadores->count();
        })->count();

        $totalEstudiantes = \App\Models\Estudiante::count();
        $totalEvaluadores = Profesor::whereHas('usuario', fn($q) => $q->where('rol', 'Evaluador'))->count();
        $totalGestores = Usuario::where('rol', 'Gestor')->count();

        // Directores vs Subdirectores
        $subdirectorIds = \Illuminate\Support\Facades\DB::table('director_trabajo')
            ->where('rol', 'subdirector')
            ->pluck('id_director')
            ->unique();
        $totalSubdirectores = $subdirectorIds->count();

        $directorIds = \Illuminate\Support\Facades\DB::table('director_trabajo')
            ->where('rol', 'director')
            ->pluck('id_director')
            ->unique();
        $totalDirectores = $directorIds->count() ?: max(0, \App\Models\Director::count() - $totalSubdirectores);

        // ── Datos estructurados para Gráficos Shadcn UI / Recharts ──
        
        // 1. Flujo de Estados
        $flowStatusData = [
            ['name' => 'Sin Evaluadores', 'value' => $sinEvaluadores, 'color' => '#f43f5e'],
            ['name' => 'En Evaluación', 'value' => $enEvaluacion, 'color' => '#3b82f6'],
            ['name' => 'Evaluados', 'value' => $evaluados, 'color' => '#10b981'],
            ['name' => 'En Revisión', 'value' => $enRevision, 'color' => '#f59e0b'],
            ['name' => 'Finalizados', 'value' => $finalizados, 'color' => '#07321e'],
        ];

        // 2. Carga Mensual
        $trabajosMes = $trabajos->filter(fn($t) => Carbon::parse($t->fecha_subida)->year == $year);
        $mesesAgrupados = $trabajosMes->groupBy(fn($t) => Carbon::parse($t->fecha_subida)->format('Y-m'));
        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $key = sprintf('%s-%02d', $year, $m);
            $nombreMes = \Carbon\Carbon::createFromFormat('Y-m', $key)->locale('es')->isoFormat('MMM');
            $monthlyData[] = [
                'mes' => ucfirst($nombreMes),
                'trabajos' => isset($mesesAgrupados[$key]) ? $mesesAgrupados[$key]->count() : 0,
            ];
        }

        // 3. Trabajos por Modalidad / Tipo
        $modalidadData = [];
        $tiposAgrupados = $trabajos->groupBy(fn($t) => optional($t->tipo)->nombre_tipo ?? 'Sin tipo');
        foreach ($tiposAgrupados as $tipo => $items) {
            $modalidadData[] = [
                'name' => $tipo,
                'total' => $items->count(),
            ];
        }

        // 4. Dictámenes y Resultados de Evaluación Emitidos por Evaluadores
        $evaluacionesAll = \App\Models\Evaluacion::all();
        $aprobadosCount = $evaluacionesAll->filter(fn($e) => in_array(strtolower(trim($e->resultado ?? '')), ['aceptada', 'puede_sustentar']))->count();
        $correccionesCount = $evaluacionesAll->filter(fn($e) => in_array(strtolower(trim($e->resultado ?? '')), ['aceptada_con_mejoras', 'sustentacion_con_correcciones']))->count();
        $rechazadosCount = $evaluacionesAll->filter(fn($e) => in_array(strtolower(trim($e->resultado ?? '')), ['rechazada', 'no_sustentar', 'requiere_reestructurar']))->count();

        $dictamenesData = [
            ['name' => 'Aprobados', 'cantidad' => $aprobadosCount, 'fill' => '#10b981'],
            ['name' => 'Con Correcciones', 'cantidad' => $correccionesCount, 'fill' => '#f59e0b'],
            ['name' => 'Rechazados / Reestructurar', 'cantidad' => $rechazadosCount, 'fill' => '#ef4444'],
        ];

        $dashboardProps = [
            'kpis' => [
                'totalTrabajos' => $totalTrabajos,
                'conEvaluadores' => $conEvaluadores,
                'sinEvaluadores' => $sinEvaluadores,
                'enEvaluacion' => $enEvaluacion,
                'evaluados' => $evaluados,
                'enRevision' => $enRevision,
                'finalizados' => $finalizados,
                'totalEstudiantes' => $totalEstudiantes,
                'totalDirectores' => $totalDirectores,
                'totalSubdirectores' => $totalSubdirectores,
                'totalEvaluadores' => $totalEvaluadores,
                'totalGestores' => $totalGestores,
            ],
            'flowStatusData' => $flowStatusData,
            'monthlyData' => $monthlyData,
            'modalidadData' => $modalidadData,
            'dictamenesData' => $dictamenesData,
            'year' => $year,
            'yearsDisponibles' => array_values($yearsDisponibles),
        ];

        return view('admin.dashboard', compact('usuario', 'dashboardProps'));
    }

    /**
     * Muestra la tabla de trabajos de grado activos (en curso).
     */
    public function trabajos()
    {
        $usuario = Auth::user();
        $trabajos = Trabajo::with(['estudiante.area.facultad', 'tipo', 'evaluadores'])
            ->where(function($q) {
                $q->where('estado', '!=', 'finalizado')
                  ->orWhereNull('estado');
            })
            ->whereNull('archivo_acta_sustentacion')
            ->orderBy('id_trabajo', 'desc')
            ->get();

        $facultades = Facultad::orderBy('nombre_facultad')->get();
        $areas = Area::orderBy('nombre_area')->get();

        return view('admin.trabajos', compact(
            'usuario',
            'trabajos',
            'facultades',
            'areas'
        ));
    }

    /**
     * Muestra la tabla de trabajos finalizados (con Acta de Sustentación subida).
     */
    public function trabajosFinalizados()
    {
        $usuario = Auth::user();
        $trabajos = Trabajo::with(['estudiante.area.facultad', 'tipo', 'evaluadores.usuario', 'evaluaciones', 'directores'])
            ->where(function($q) {
                $q->where('estado', 'finalizado')
                  ->orWhereNotNull('archivo_acta_sustentacion');
            })
            ->orderBy('id_trabajo', 'desc')
            ->get();

        $facultades = Facultad::orderBy('nombre_facultad')->get();
        $areas = Area::orderBy('nombre_area')->get();

        return view('admin.trabajosFinalizados', compact(
            'usuario',
            'trabajos',
            'facultades',
            'areas'
        ));
    }

    /**
     * Muestra la lista de Evaluadores agrupados por su Área de conocimiento.
     */
    public function evaluadores()
    {
        $usuario = Auth::user();
        $evaluadores = Profesor::whereHas('usuario', function($q) {
            $q->where('rol', 'Evaluador');
        })
        ->with(['usuario', 'area.facultad', 'trabajos.evaluaciones'])
        ->get();

        $evaluadoresPorArea = $evaluadores->groupBy(function($p) {
            return optional($p->area)->nombre_area ?? 'Sin Área Asignada';
        });

        $facultades = Facultad::orderBy('nombre_facultad')->get();
        $areas = Area::orderBy('nombre_area')->get();

        return view('admin.evaluadores', compact(
            'usuario',
            'evaluadores',
            'evaluadoresPorArea',
            'facultades',
            'areas'
        ));
    }

    /**
     * Exporta el Reporte General de Trabajos de Grado y Evaluadores en formato Excel Multi-Hoja.
     */
    public function exportarExcel(Request $request)
    {
        $year = (int) $request->input('year', date('Y'));

        $reporteService = new \App\Services\ReporteExcelService();
        $tempPath = $reporteService->generarReporte($year);

        $fileName = "Reporte_General_Trabajos_y_Evaluadores_{$year}.xlsx";

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Genera la vista de rúbrica en PDF de una evaluación específica para el Administrador.
     */
    public function rubricaPDF($id)
    {
        $usuario = Auth::user();
        $evaluacion = Evaluacion::with([
            'trabajo.tipo',
            'trabajo.estudiante',
            'trabajo.directores',
            'trabajo.evaluadores.usuario',
            'profesor.usuario'
        ])->find($id);

        if (!$evaluacion) {
            $evaluacion = Evaluacion::where('id_trabajo', $id)->with([
                'trabajo.tipo',
                'trabajo.estudiante',
                'trabajo.directores',
                'trabajo.evaluadores.usuario',
                'profesor.usuario'
            ])->first();
        }

        if (!$evaluacion) {
            $trabajo = Trabajo::with(['tipo', 'estudiante', 'directores', 'evaluadores.usuario'])->find($id);
            if (!$trabajo) {
                abort(404, 'Trabajo de grado no encontrado.');
            }

            $evaluacion = new Evaluacion();
            $evaluacion->id_trabajo = $trabajo->id_trabajo;
            $evaluacion->id_profesor = $trabajo->evaluadores->first()->id_profesor ?? null;
            $evaluacion->tipo_plantilla = $trabajo->plantilla_rubrica ?? 'propuesta_de_grado';
            $evaluacion->nota_final = null;
            $evaluacion->resultado = '';
            $evaluacion->observaciones_globales = '';
            $evaluacion->criterios = [];
            $evaluacion->setRelation('trabajo', $trabajo);
            if ($trabajo->evaluadores->first()) {
                $evaluacion->setRelation('profesor', $trabajo->evaluadores->first());
            }
        }

        return view('evaluador.rubrica_pdf', compact('usuario', 'evaluacion'));
    }

    /**
     * Muestra la interfaz para asignar evaluadores (profesores) a un trabajo de grado.
     */
    public function asignarEvaluador(Request $request, $trabajo_id)
    {
        $usuario = Auth::user();
        $isEditing = $request->has('edit');

        $trabajo = Trabajo::with([
            'estudiante.area.facultad',
            'tipo',
            'evaluadores' => function ($query) {
                $query->withPivot('fecha_asignacion', 'decision_evaluador', 'motivo_rechazo');
            },
            'evaluadores.usuario',
            'evaluadores.area.facultad',
        ])->findOrFail($trabajo_id);

        if (!$trabajo->tieneActa()) {
            return redirect()->route('admin.detallesTrabajo', ['id' => $trabajo_id, 'ir' => 'acta'])
                ->with('warning', 'Debe adjuntar el Acta del proyecto antes de poder asignar evaluadores.');
        }

        // IDs de áreas de los estudiantes para auto-filtrado
        $studentAreaIds = $trabajo->estudiante
            ->map(fn($est) => optional($est->area)->id_area)
            ->filter()->unique()->values();

        // IDs de evaluadores ya asignados a este trabajo
        $evaluadoresAsignadosIds = $trabajo->evaluadores->pluck('id_profesor')->all();

        // Todos los evaluadores con rol 'Evaluador'
        $evaluadores = Profesor::whereHas('usuario', function ($query) {
            $query->where('rol', 'Evaluador');
        })->with(['usuario', 'area.facultad'])->withCount('trabajos')->orderBy('id_profesor')->get();

        // Separar asignados, y filtrar por área del estudiante el resto
        $evaluadoresSinAsignar = $evaluadores->reject(
            fn($ev) => in_array($ev->id_profesor, $evaluadoresAsignadosIds)
        );

        if ($studentAreaIds->isNotEmpty()) {
            $evaluadoresSinAsignar = $evaluadoresSinAsignar->whereIn('id_area', $studentAreaIds->all());
        }

        // Asignados: los que ya están en este trabajo (con pivot data)
        $evaluadoresAsignados = $trabajo->evaluadores;

        $evaluadoresDisponibles = $evaluadoresSinAsignar->values();

        $evaluadoresNoDisponibles = $evaluadores
            ->reject(fn($ev) => in_array($ev->id_profesor, $evaluadoresAsignadosIds))
            ->values();

        if ($studentAreaIds->isNotEmpty()) {
            $evaluadoresNoDisponibles = $evaluadoresNoDisponibles
                ->reject(fn($ev) => in_array($ev->id_area, $studentAreaIds->all()))
                ->values();
        }

        $evaluadoresCatalogo = $evaluadoresDisponibles
            ->merge($evaluadoresAsignados)
            ->keyBy('id_profesor')
            ->map(function ($ev) use ($evaluadoresAsignadosIds) {
                return [
                    'id' => $ev->id_profesor,
                    'nombre' => trim(($ev->usuario->nombre ?? '') . ' ' . ($ev->usuario->apellido ?? '')),
                    'correo' => $ev->usuario->correo ?? '',
                    'iniciales' => strtoupper(substr($ev->usuario->nombre ?? 'N', 0, 1) . substr($ev->usuario->apellido ?? '', 0, 1)),
                    'facultad' => optional(optional($ev->area)->facultad)->nombre_facultad ?? 'N/A',
                    'area' => $ev->area->nombre_area ?? 'N/A',
                    'carga' => $ev->trabajos_count,
                    'ya_asignado' => in_array($ev->id_profesor, $evaluadoresAsignadosIds, true),
                    'fecha_asignacion' => $ev->pivot->fecha_asignacion ?? null,
                    'decision_evaluador' => $ev->pivot->decision_evaluador ?? null,
                    'motivo_rechazo' => $ev->pivot->motivo_rechazo ?? null,
                ];
            })
            ->values();

        $facultades = \App\Models\Facultad::with('areas')->get();

        return view('admin.asignarEvaluador', compact(
            'usuario', 'trabajo', 'evaluadoresDisponibles', 'evaluadoresNoDisponibles',
            'evaluadoresAsignados', 'evaluadoresAsignadosIds', 'evaluadoresCatalogo',
            'isEditing', 'facultades', 'studentAreaIds'
        ));
    }

    /**
     * Guarda la asignación de evaluadores (profesores) para un trabajo de grado.
     */
    public function guardarEvaluadores(Request $request, $trabajo_id)
    {
        $trabajo = Trabajo::with('evaluadores')->findOrFail($trabajo_id);

        if (!$trabajo->tieneActa()) {
            return redirect()->route('admin.detallesTrabajo', $trabajo_id)
                ->with('error', 'No se pueden asignar evaluadores sin haber subido previamente el Acta del proyecto.');
        }

        $teniaEvaluadores = $trabajo->evaluadores->isNotEmpty();
        $evaluadoresAnteriores = $trabajo->evaluadores->keyBy('id_profesor');
        $evaluadoresSeleccionados = array_values(array_unique($request->input('evaluadores', [])));

        $rules = [
            'evaluadores' => $teniaEvaluadores ? 'nullable|array|max:2' : 'required|array|min:1|max:2',
            'evaluadores.*' => 'exists:profesor,id_profesor',
        ];

        $request->validate($rules, [
            'evaluadores.required' => 'Debes seleccionar al menos un evaluador.',
            'evaluadores.min' => 'Debes seleccionar al menos un evaluador.',
            'evaluadores.max' => 'Solo puedes seleccionar un máximo de 2 evaluadores.',
            'evaluadores.*.exists' => 'Uno de los evaluadores seleccionados no existe como Profesor.',
        ]);

        try {
            DB::beginTransaction();

            $fechaAsignacion = Carbon::now();
            $fechaLimite = BusinessDaysService::addBusinessDays($fechaAsignacion, 15);
            $defaultPivotData = [
                'fecha_asignacion' => $fechaAsignacion,
                'fecha_limite_revision' => $fechaLimite,
                'estado_revision' => 'Pendiente',
            ];

            if (empty($evaluadoresSeleccionados)) {
                $trabajo->evaluadores()->detach();

                if ($trabajo->estado === 'en_revision') {
                    $trabajo->update(['estado' => 'subido']);
                }

                \App\Models\HistorialEstado::create([
                    'trabajo_grado_id' => $trabajo->id_trabajo,
                    'estado' => $trabajo->estado,
                    'user_id' => Auth::id(),
                    'observacion_estado' => 'Evaluadores removidos del proyecto.',
                ]);

                DB::commit();

                return redirect()->route('admin.detallesTrabajo', $trabajo_id)
                    ->with('success', 'Evaluadores removidos correctamente.');
            }

            $dataToSync = [];
            $nuevosEvaluadores = [];

            foreach ($evaluadoresSeleccionados as $profesorId) {
                if ($evaluadoresAnteriores->has($profesorId)) {
                    $existente = $evaluadoresAnteriores->get($profesorId);
                    $dataToSync[$profesorId] = [
                        'fecha_asignacion' => $existente->pivot->fecha_asignacion,
                        'fecha_limite_revision' => $existente->pivot->fecha_limite_revision,
                        'estado_revision' => $existente->pivot->estado_revision,
                    ];
                } else {
                    $dataToSync[$profesorId] = $defaultPivotData;
                    $nuevosEvaluadores[] = $profesorId;
                }
            }

            $trabajo->evaluadores()->sync($dataToSync);
            $trabajo->update(['estado' => 'en_revision']);

            $nombresEvaluadores = \App\Models\Profesor::with('usuario')
                ->whereIn('id_profesor', $evaluadoresSeleccionados)
                ->get()
                ->map(fn($p) => $p->usuario ? trim($p->usuario->nombre . ' ' . $p->usuario->apellido) : ('Evaluador #' . $p->id_profesor))
                ->filter()
                ->implode(', ');

            $observacion = $teniaEvaluadores
                ? 'Asignación de evaluadores actualizada: ' . $nombresEvaluadores . '.'
                : 'Evaluadores asignados: ' . $nombresEvaluadores . '. Proyecto entra en revisión.';

            \App\Models\HistorialEstado::create([
                'trabajo_grado_id' => $trabajo->id_trabajo,
                'estado' => 'evaluadores_asignados',
                'user_id' => Auth::id(),
                'observacion_estado' => $observacion,
            ]);

            DB::commit();

            $trabajo->load(['evaluadores.usuario', 'estudiante', 'tipo']);
            $idsNuevosEvaluadores = array_map('intval', $nuevosEvaluadores);

            // Notificar por correo y en sistema a evaluadores asignados
            foreach ($trabajo->evaluadores as $evaluador) {
                $idEvaluador = (int) $evaluador->id_profesor;

                if (in_array($idEvaluador, $idsNuevosEvaluadores, true) && $evaluador->usuario) {
                    $evaluador->usuario->notify(new EvaluadorAsignado($trabajo, $fechaLimite));

                    if (!empty($evaluador->usuario->correo)) {
                        try {
                            $nombreEval = trim($evaluador->usuario->nombre . ' ' . $evaluador->usuario->apellido);
                            Mail::to($evaluador->usuario->correo)->send(new EvaluadorAsignadoMailable(
                                $trabajo,
                                $nombreEval,
                                $fechaLimite,
                                15
                            ));
                        } catch (\Throwable $e) {
                            \Log::error('Error al enviar correo de asignación a evaluador: ' . $e->getMessage());
                        }
                    }
                }
            }

            // Notificar por correo a estudiantes
            foreach ($trabajo->estudiante as $est) {
                if (!empty($est->correo)) {
                    try {
                        Mail::to($est->correo)->send(new EstudianteEvaluadoresAsignadosMailable(
                            $trabajo,
                            trim($est->nombre . ' ' . $est->apellido)
                        ));
                    } catch (\Throwable $e) {
                        \Log::error('Error al enviar correo de asignación de evaluadores a estudiante: ' . $e->getMessage());
                    }
                }
            }

            $mensaje = $teniaEvaluadores
                ? 'Asignación de evaluadores actualizada correctamente.'
                : 'Evaluadores asignados correctamente. La fecha límite de revisión es el ' . $fechaLimite->format('d/m/Y') . ' (15 Días Hábiles).';

            return redirect()->route('admin.detallesTrabajo', $trabajo_id)->with('success', $mensaje);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Hubo un error al guardar la asignación: ' . $e->getMessage());
        }
    }
    public function listaEstudiantes(Request $request)
    {
        $usuario = Auth::user();

        // Paginamos por Trabajo (proyecto), cargando sus estudiantes
        $query = \App\Models\Trabajo::with(['estudiante.area.facultad', 'tipo'])
            ->whereHas('estudiante');

        // Filtrar por facultad del estudiante
        if ($request->filled('id_facultad')) {
            $query->whereHas('estudiante.area', fn($q) => $q->where('id_facultad', $request->id_facultad));
        }

        // Filtrar por área del estudiante
        if ($request->filled('id_area')) {
            $query->whereHas('estudiante', fn($q) => $q->where('id_area', $request->id_area));
        }

        // Búsqueda por nombre/apellido/correo del estudiante
        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->whereHas('estudiante', fn($q) => $q
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhere('apellido', 'like', "%{$busqueda}%")
                ->orWhere('correo', 'like', "%{$busqueda}%")
            );
        }

        $trabajosPaginados = $query->orderBy('id_trabajo', 'desc')->paginate(10)->withQueryString();

        $facultades = Facultad::orderBy('nombre_facultad')->get();
        $areas = Area::orderBy('nombre_area')->get();

        return view('admin.listaEstudiantes', compact('usuario', 'trabajosPaginados', 'facultades', 'areas'));
    }

    public function detallesTrabajo($id)
    {
        $usuario = Auth::user();
        $trabajo = Trabajo::with(['estudiante.area', 'tipo', 'evaluadores.usuario', 'rubricas', 'historialEstados.usuario', 'directores', 'evaluaciones.profesor.usuario'])->findOrFail($id);
        
        // Inicializar el historial de estados para proyectos creados antes de esta implementación (datos legados)
        if ($trabajo->historialEstados->isEmpty()) {
            // 1. Hito 'subido' inicial
            \App\Models\HistorialEstado::create([
                'trabajo_grado_id' => $trabajo->id_trabajo,
                'estado' => 'subido',
                'user_id' => $usuario->id_usuario, // Administrador que visualiza y corrige el registro
                'observacion_estado' => 'Proyecto registrado en la plataforma (Historial inicializado automáticamente).',
                'created_at' => $trabajo->created_at ?? now(),
                'updated_at' => $trabajo->created_at ?? now(),
            ]);

            // 2. Hito 'en_revision' si ya cuenta con evaluadores
            if ($trabajo->evaluadores->count() > 0) {
                $fechaAsignacion = $trabajo->evaluadores->first()->pivot->fecha_asignacion ?? now();
                \App\Models\HistorialEstado::create([
                    'trabajo_grado_id' => $trabajo->id_trabajo,
                    'estado' => 'en_revision',
                    'user_id' => $usuario->id_usuario,
                    'observacion_estado' => 'Evaluadores asignados para revisión (Historial inicializado automáticamente).',
                    'created_at' => $fechaAsignacion,
                    'updated_at' => $fechaAsignacion,
                ]);
            }

            // Recargar la relación para mostrarla en la vista inmediatamente
            $trabajo->load('historialEstados.usuario');
        }
        
        return view('admin.detallesTrabajo', compact('usuario', 'trabajo'));
    }

    public function historial(Request $request)
    {
        $usuario = Auth::user();

        $query = \App\Models\HistorialEstado::with(['trabajo', 'usuario'])
            ->whereIn('estado', ['evaluador_rechazo', 'estudiante_eliminado'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('tipo')) {
            $query->where('estado', $request->tipo);
        }
        if ($request->filled('busqueda')) {
            $search = $request->busqueda;
            $query->where(function ($q) use ($search) {
                $q->where('observacion_estado', 'ilike', "%{$search}%")
                  ->orWhereHas('trabajo', fn($sq) => $sq->where('titulo', 'ilike', "%{$search}%"));
            });
        }

        $eventos = $query->paginate(20);

        return view('admin.historial', compact('usuario', 'eventos'));
    }

    #[OA\Delete(
        path: '/admin/estudiante/eliminar/{id}',
        summary: 'Eliminar estudiante con motivo',
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'motivo', type: 'string', description: 'Motivo de la eliminación'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Estudiante eliminado', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 400, description: 'Motivo requerido'),
            new OA\Response(response: 404, description: 'Estudiante no encontrado'),
            new OA\Response(response: 500, description: 'Error al eliminar'),
        ]
    )]
    public function eliminarEstudiante(Request $request, $id)
    {
        try {
            $estudiante = \App\Models\Estudiante::findOrFail($id);

            $request->validate([
                'motivo' => 'required|string|max:1000',
            ]);

            $motivo = $request->input('motivo');

            // Guardar motivo y crear historial antes de eliminar
            $estudiante->motivo_eliminacion = $motivo;

            if ($estudiante->trabajo) {
                \App\Models\HistorialEstado::create([
                    'trabajo_grado_id' => $estudiante->trabajo->id_trabajo,
                    'estado' => 'estudiante_eliminado',
                    'user_id' => Auth::id(),
                    'observacion_estado' => "Estudiante '{$estudiante->nombre} {$estudiante->apellido}' eliminado del proyecto. Motivo: {$motivo}",
                ]);
            }

            $estudiante->delete();

            return response()->json(['success' => true, 'message' => 'Estudiante eliminado correctamente.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'El motivo de eliminación es requerido.'], 400);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Prorroga el plazo de revisión para un evaluador de un trabajo específico.
     */
    #[OA\Post(
        path: '/admin/trabajo-evaluador/prorrogar',
        summary: 'Prorrogar plazo de revisión',
        tags: ['Admin'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'id_trabajo', type: 'integer'),
                    new OA\Property(property: 'id_profesor', type: 'integer'),
                    new OA\Property(property: 'dias', type: 'integer', description: 'Días a prorrogar (solo 21)'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Plazo prorrogado', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean'),
                new OA\Property(property: 'nueva_fecha', type: 'string'),
                new OA\Property(property: 'dias_restantes', type: 'integer'),
            ])),
            new OA\Response(response: 500, description: 'Error al prorrogar'),
        ]
    )]
    public function prorrogarPlazo(Request $request)
    {
        $request->validate([
            'id_trabajo' => 'required|exists:trabajo,id_trabajo',
            'id_profesor' => 'required|exists:profesor,id_profesor',
            'dias' => 'required|integer|in:21',
        ]);

        try {
            $trabajo = Trabajo::findOrFail($request->id_trabajo);
            $evaluador = $trabajo->evaluadores()
                ->where('trabajo_profesor.id_profesor', $request->id_profesor)
                ->firstOrFail();

            // Usar la fecha actual si la fecha límite ya venció, sino añadir sobre la fecha límite actual
            $currentDeadline = Carbon::parse($evaluador->pivot->fecha_limite_revision);
            if ($currentDeadline->isPast()) {
                $newDeadline = Carbon::now()->addDays($request->dias);
            } else {
                $newDeadline = $currentDeadline->addDays($request->dias);
            }

            $trabajo->evaluadores()->updateExistingPivot($request->id_profesor, [
                'fecha_limite_revision' => $newDeadline
            ]);

            // ── Notificar al evaluador del plazo extendido ──
            $profesor = Profesor::find($request->id_profesor);
            if ($profesor && $profesor->usuario) {
                $profesor->usuario->notify(new PlazoExtendido($trabajo, $newDeadline));
            }

            $diasRestantes = (int) Carbon::now()->diffInDays($newDeadline, false);

            return response()->json([
                'success' => true,
                'nueva_fecha' => $newDeadline->format('d/m/Y'),
                'nueva_fecha_larga' => $newDeadline->format('d \d\e F, Y'),
                'dias_restantes' => $diasRestantes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al prorrogar el plazo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprueba oficialmente el trabajo de grado y registra el hito.
     */
    #[OA\Post(
        path: '/admin/trabajo/{id}/aprobar',
        summary: 'Aprobar trabajo de grado',
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'observacion_estado', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 302, description: 'Redirect con mensaje de éxito'),
            new OA\Response(response: 404, description: 'Trabajo no encontrado'),
        ]
    )]
    public function aprobarTrabajo(Request $request, $id)
    {
        $trabajo = Trabajo::findOrFail($id);

        $trabajo->update(['estado' => 'aprobado']);

        \App\Models\HistorialEstado::create([
            'trabajo_grado_id' => $trabajo->id_trabajo,
            'estado' => 'aprobado',
            'user_id' => Auth::id(),
            'observacion_estado' => $request->input('observacion_estado') ?? 'El proyecto de grado ha sido aprobado oficialmente.',
        ]);

        // ── Notificar a todos los Gestores ──
        $gestores = Usuario::where('rol', 'Gestor')->where('activo', true)->get();
        foreach ($gestores as $gestor) {
            $gestor->notify(new TrabajoAprobado($trabajo));
        }

        return redirect()->route('admin.detallesTrabajo', $id)->with('success', 'El proyecto de grado ha sido aprobado con éxito.');
    }

    public function quitarEvaluadores($id)
    {
        $trabajo = Trabajo::findOrFail($id);

        if ($trabajo->evaluadores->count() === 0) {
            return redirect()->route('admin.detallesTrabajo', $id)
                ->with('error', 'El proyecto no tiene evaluadores asignados.');
        }

        DB::beginTransaction();
        try {
            $trabajo->evaluadores()->detach();

            if ($trabajo->estado === 'en_revision') {
                $trabajo->update(['estado' => 'subido']);
            }

            \App\Models\HistorialEstado::create([
                'trabajo_grado_id' => $trabajo->id_trabajo,
                'estado' => $trabajo->estado,
                'user_id' => Auth::id(),
                'observacion_estado' => 'Evaluadores removidos del proyecto.',
            ]);

            DB::commit();

            return redirect()->route('admin.detallesTrabajo', $id)
                ->with('success', 'Evaluadores removidos correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.detallesTrabajo', $id)
                ->with('error', 'Error al remover evaluadores: ' . $e->getMessage());
        }
    }

    #[OA\Post(
        path: '/admin/trabajo/{id}/retirar',
        summary: 'Retirar o reactivar trabajo de grado',
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 302, description: 'Redirect con mensaje de éxito'),
            new OA\Response(response: 404, description: 'Trabajo no encontrado'),
        ]
    )]
    public function retirar(Request $request, $id)
    {
        if (!auth()->check() || auth()->user()->rol !== 'Administrador') {
            abort(403, 'Solo el administrador puede retirar o reactivar trabajos.');
        }

        $trabajo = Trabajo::findOrFail($id);

        $retirado = !$trabajo->retirado;
        $trabajo->update(['retirado' => $retirado]);

        \App\Models\HistorialEstado::create([
            'trabajo_grado_id' => $trabajo->id_trabajo,
            'estado' => $trabajo->estado,
            'user_id' => Auth::id(),
            'observacion_estado' => $retirado
                ? 'Proyecto retirado por el administrador. Se desvincularon los evaluadores asignados.'
                : 'Proyecto reactivado por el administrador.',
        ]);

        // ── Notificar a todos los Administradores ──
        $nombreActor = 'El administrador ' . Auth::user()->nombre . ' ' . Auth::user()->apellido;
        $admins = Usuario::where('rol', 'Administrador')->where('activo', true)->get();
        foreach ($admins as $admin) {
            if ($retirado) {
                $admin->notify(new TrabajoRetirado($trabajo, $nombreActor));
            } else {
                $admin->notify(new TrabajoReactivado($trabajo, $nombreActor));
            }
        }

        // ── Desvincular evaluadores asignados si el proyecto fue retirado ──
        if ($retirado) {
            // Notificar primero a los evaluadores aún vinculados
            $evaluadores = $trabajo->evaluadores;
            foreach ($evaluadores as $evaluador) {
                if ($evaluador->usuario) {
                    $evaluador->usuario->notify(new TrabajoRetiradoEvaluador($trabajo, $nombreActor));
                }
            }

            $trabajo->evaluadores()->detach();
        }

        $mensaje = $retirado
            ? 'Proyecto desactivado correctamente.'
            : 'Proyecto reactivado correctamente.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()
            ->with('success', $mensaje);
    }

    #[OA\Delete(
        path: '/admin/trabajo/{id}/eliminar',
        summary: 'Eliminar trabajo de grado',
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 302, description: 'Redirect con mensaje de éxito'),
            new OA\Response(response: 404, description: 'Trabajo no encontrado'),
        ]
    )]
    public function eliminarTrabajo(Request $request, $id)
    {
        if (!auth()->check() || auth()->user()->rol !== 'Administrador') {
            abort(403, 'Solo el administrador puede eliminar trabajos.');
        }

        $trabajo = Trabajo::findOrFail($id);

        // ── Solo se puede eliminar un trabajo previamente desactivado ──
        if (!$trabajo->retirado) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes desactivar el trabajo antes de poder eliminarlo.',
                ]);
            }

            return redirect()->route('admin.trabajos')
                ->with('error', 'Debes desactivar el trabajo antes de poder eliminarlo.');
        }

        // ── Notificar a todos los Administradores antes de eliminar ──
        $nombreActor = 'El administrador ' . Auth::user()->nombre . ' ' . Auth::user()->apellido;
        $admins = Usuario::where('rol', 'Administrador')->where('activo', true)->get();
        foreach ($admins as $admin) {
            $admin->notify(new TrabajoEliminado($trabajo, $nombreActor));
        }

        // ── Notificar a los evaluadores asignados antes de eliminar ──
        $evaluadores = $trabajo->evaluadores;
        foreach ($evaluadores as $evaluador) {
            if ($evaluador->usuario) {
                $evaluador->usuario->notify(new TrabajoEliminadoEvaluador($trabajo, $nombreActor));
            }
        }

        DB::transaction(function () use ($trabajo) {
            // Detach pivot relationships
            $trabajo->evaluadores()->detach();
            $trabajo->rubricas()->detach();
            $trabajo->directores()->detach();

            // Delete other related records that don't cascade delete
            DB::table('trabajo_estudiante')->where('id_trabajo', $trabajo->id_trabajo)->delete();
            DB::table('alerta')->where('id_trabajo', $trabajo->id_trabajo)->delete();
            DB::table('seguimiento')->where('id_trabajo', $trabajo->id_trabajo)->delete();

            // Delete retroalimentaciones and historialEstados
            $trabajo->retroalimentaciones()->delete();
            $trabajo->historialEstados()->delete();

            // Delete students
            DB::table('estudiante')->where('id_trabajo', $trabajo->id_trabajo)->delete();

            // Delete original model (will trigger file deletion via observer)
            $trabajo->delete();
        });

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.trabajos')
            ->with('success', 'Proyecto eliminado permanentemente del sistema.');
    }

    /**
     * Sube o actualiza el archivo del Acta para el trabajo de grado.
     */
    public function subirActa(Request $request, $id)
    {
        $trabajo = Trabajo::findOrFail($id);

        $request->validate([
            'archivo_acta' => 'required|file|mimes:pdf,doc,docx|max:10240', // max 10MB
        ], [
            'archivo_acta.required' => 'Debes seleccionar un archivo para el Acta.',
            'archivo_acta.mimes' => 'El Acta debe ser un archivo en formato PDF, DOC o DOCX.',
            'archivo_acta.max' => 'El archivo no debe superar los 10MB.',
        ]);

        if ($request->hasFile('archivo_acta')) {
            // Eliminar acta anterior si existe
            if ($trabajo->archivo_acta) {
                $relative = preg_replace('#^storage/#', '', $trabajo->archivo_acta);
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($relative)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($relative);
                }
            }

            $esActualizacion = $trabajo->archivo_acta ? true : false;

            // Guardar el acta conservando el nombre original del archivo (limpio)
            $archivo = $request->file('archivo_acta');
            $nombreLimpio = Str::slug(pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME));
            $extension = $archivo->getClientOriginalExtension();
            $nombreArchivo = $nombreLimpio . '.' . $extension;

            // Si ya existe un archivo con ese nombre, agregarle un sufijo
            $contador = 1;
            while (\Illuminate\Support\Facades\Storage::disk('public')->exists("actas/{$nombreArchivo}")) {
                $nombreArchivo = "{$nombreLimpio}-{$contador}.{$extension}";
                $contador++;
            }

            $path = $archivo->storeAs('actas', $nombreArchivo, 'public');
            $trabajo->archivo_acta = 'storage/' . $path;
            $trabajo->save();

            \App\Models\HistorialEstado::create([
            'trabajo_grado_id' => $trabajo->id_trabajo,
            'estado' => 'acta_subida',
            'user_id' => Auth::id(),
                'observacion_estado' => $esActualizacion
                    ? 'El Acta de inicio/aprobación fue actualizada correctamente.'
                    : 'El Acta de inicio/aprobación fue subida correctamente.',
            ]);
        }

        return redirect()->back()->with('success', 'El Acta del proyecto se ha adjuntado correctamente.');
    }

    /**
     * Sube o actualiza el Acta de Sustentación del trabajo de grado.
     * Al adjuntar este documento, el proceso del proyecto finaliza.
     */
    public function subirActaSustentacion(Request $request, $id)
    {
        $trabajo = Trabajo::findOrFail($id);

        // Solo aplica para trabajos que ya son Trabajo de Grado
        if ($trabajo->plantilla_rubrica !== 'trabajo_de_grado') {
            return redirect()->route('admin.detallesTrabajo', $id)
                ->with('error', 'El Acta de Sustentación solo puede adjuntarse a un Trabajo de Grado.');
        }

        $request->validate([
            'archivo_acta_sustentacion' => 'required|file|mimes:pdf,doc,docx|max:10240', // max 10MB
        ], [
            'archivo_acta_sustentacion.required' => 'Debes seleccionar un archivo para el Acta de Sustentación.',
            'archivo_acta_sustentacion.mimes' => 'El Acta de Sustentación debe ser un archivo en formato PDF, DOC o DOCX.',
            'archivo_acta_sustentacion.max' => 'El archivo no debe superar los 10MB.',
        ]);

        if ($request->hasFile('archivo_acta_sustentacion')) {
            // Eliminar acta anterior si existe
            if ($trabajo->archivo_acta_sustentacion) {
                $relative = preg_replace('#^storage/#', '', $trabajo->archivo_acta_sustentacion);
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($relative)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($relative);
                }
            }

            $esActualizacion = $trabajo->archivo_acta_sustentacion ? true : false;

            // Guardar el acta conservando el nombre original (con prefijo para distinguirla del acta de inicio)
            $archivo = $request->file('archivo_acta_sustentacion');
            $nombreLimpio = Str::slug(pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME));
            $extension = $archivo->getClientOriginalExtension();
            $nombreArchivo = 'sustentacion-' . $nombreLimpio . '.' . $extension;

            // Si ya existe un archivo con ese nombre, agregarle un sufijo
            $contador = 1;
            while (\Illuminate\Support\Facades\Storage::disk('public')->exists("actas/{$nombreArchivo}")) {
                $nombreArchivo = "sustentacion-{$nombreLimpio}-{$contador}.{$extension}";
                $contador++;
            }

            $path = $archivo->storeAs('actas', $nombreArchivo, 'public');
            $trabajo->archivo_acta_sustentacion = 'storage/' . $path;
            $trabajo->estado = 'finalizado';
            $trabajo->save();

            \App\Models\HistorialEstado::create([
                'trabajo_grado_id' => $trabajo->id_trabajo,
                'estado' => 'acta_sustentacion_subida',
                'user_id' => Auth::id(),
                'observacion_estado' => $esActualizacion
                    ? 'El Acta de Sustentación fue actualizada. El proceso del proyecto continúa finalizado.'
                    : 'El Acta de Sustentación fue subida. El proceso del proyecto ha finalizado.',
            ]);

            // ── Notificar a todos los Gestores ──
            try {
                $gestores = Usuario::where('rol', 'Gestor')->where('activo', true)->get();
                foreach ($gestores as $gestor) {
                    $gestor->notify(new \App\Notifications\ActaSustentacionSubida($trabajo));
                }
            } catch (\Throwable $e) {
                \Log::error('Error al notificar gestores del acta de sustentación: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Acta de Sustentación adjuntada. El proceso del proyecto ha finalizado.');
    }
}
