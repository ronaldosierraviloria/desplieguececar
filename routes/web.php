<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Gestor\GestorController;
use App\Http\Controllers\Gestor\TrabajoController;
use App\Http\Controllers\Evaluador\EvaluadorController;
use App\Http\Controllers\Evaluador\ControllerEvaluador;
use App\Http\Controllers\Gestor\AsignarRubricaController;
use App\Http\Controllers\Admin\AdminGestorController;
use App\Http\Controllers\Admin\AdminAreaController;
use App\Http\Controllers\Admin\AdminFacultadController;
use App\Http\Controllers\Admin\AdminTipoTrabajoController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificacionController;

// --- AUTENTICACIÓN ---
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', function () {
    return redirect()->route('login');
});

// --- RUTAS COMPARTIDAS (Cualquier usuario autenticado y activo) ---
Route::middleware(['auth', 'check.activo'])->group(function () {
    Route::post('/session/ping', function () {
        return response()->json(['status' => 'alive']);
    })->name('session.ping');

    // Notificaciones
    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::get('/notificaciones/panel', [NotificacionController::class, 'panel'])->name('notificaciones.panel');
    Route::post('/notificaciones/{id}/leida', [NotificacionController::class, 'marcarLeida'])->name('notificaciones.leida');
    Route::post('/notificaciones/todas-leidas', [NotificacionController::class, 'marcarTodasLeidas'])->name('notificaciones.todas.leidas');
    Route::delete('/notificaciones/todas', [NotificacionController::class, 'destroyAll'])->name('notificaciones.destroyAll');

    // Perfil
    Route::get('/perfil', [UserController::class, 'perfil'])->name('user.perfil');
    Route::put('/perfil', [UserController::class, 'update'])->name('user.perfil.update');
});

// --- MÓDULO ADMINISTRADOR ---
Route::middleware(['auth', 'check.activo', 'check.role:Administrador'])
    ->prefix(config('app.admin_prefix', 'admin'))
    ->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/trabajos', [AdminController::class, 'trabajos'])->name('admin.trabajos');
        Route::get('/trabajos-finalizados', [AdminController::class, 'trabajosFinalizados'])->name('admin.trabajos-finalizados');
        Route::get('/evaluadores', [AdminController::class, 'evaluadores'])->name('admin.evaluadores');
        Route::get('/exportar-excel', [AdminController::class, 'exportarExcel'])->name('admin.exportarExcel');
        Route::get('/evaluacion/{id}/rubrica-pdf', [AdminController::class, 'rubricaPDF'])->name('admin.rubrica-pdf');
        Route::get('/asignar-evaluador/{trabajo_id}', [AdminController::class, 'asignarEvaluador'])->name('admin.asignarEvaluador');
        Route::post('/guardar-evaluadores/{trabajo_id}', [AdminController::class, 'guardarEvaluadores'])->name('admin.guardarEvaluador');
        Route::post('/evaluadores/guardar/{id}', [EvaluadorController::class, 'guardarEvaluadores'])->name('evaluadores.guardar');
        
        // Usuarios (Admin, Gestor)
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('admin.usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('admin.usuarios.store');
        Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('admin.usuarios.update');
        Route::post('/usuarios/{id}/toggle', [UsuarioController::class, 'toggleActive'])->name('admin.usuarios.toggle');
        Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])->name('admin.usuarios.destroy');
        
        // Proyectos
        Route::get('/proyecto/{id}', [AdminController::class, 'detallesTrabajo'])->name('admin.detallesTrabajo');
        Route::delete('/estudiante/eliminar/{id}', [AdminController::class, 'eliminarEstudiante'])->name('admin.eliminarEstudiante');
        Route::post('/trabajo-evaluador/prorrogar', [AdminController::class, 'prorrogarPlazo'])->name('admin.evaluador.prorrogar');
        Route::post('/trabajo/{id}/aprobar', [AdminController::class, 'aprobarTrabajo'])->name('admin.trabajo.aprobar');
        Route::post('/trabajo/{id}/retirar', [AdminController::class, 'retirar'])->name('admin.trabajo.retirar');
        Route::post('/trabajo/{id}/quitar-evaluadores', [AdminController::class, 'quitarEvaluadores'])->name('admin.trabajo.quitarEvaluadores');
        Route::post('/trabajo/{id}/subir-acta', [AdminController::class, 'subirActa'])->name('admin.trabajo.subirActa');
        Route::post('/trabajo/{id}/subir-acta-sustentacion', [AdminController::class, 'subirActaSustentacion'])->name('admin.trabajo.subirActaSustentacion');
        Route::delete('/trabajo/{id}/eliminar', [AdminController::class, 'eliminarTrabajo'])->name('admin.trabajo.eliminar');
        
        // Lista de Estudiantes
        Route::get('/lista-estudiantes', [AdminController::class, 'listaEstudiantes'])->name('admin.listaEstudiantes');

        // Historial
        Route::get('/historial', [AdminController::class, 'historial'])->name('admin.historial');

        // Facultades y Áreas
        Route::get('/facultades-areas', [AdminAreaController::class, 'index'])->name('admin.listaAreas');
        Route::post('/agregar-area', [AdminAreaController::class, 'store'])->name('admin.area.store');
        Route::put('/area/{id}', [AdminAreaController::class, 'update'])->name('admin.area.update');
        Route::delete('/eliminar-area/{id}', [AdminAreaController::class, 'destroy'])->name('admin.area.destroy');
        
        Route::post('/agregar-facultad', [AdminFacultadController::class, 'store'])->name('admin.facultad.store');
        Route::put('/facultad/{id}', [AdminFacultadController::class, 'update'])->name('admin.facultad.update');
        Route::delete('/eliminar-facultad/{id}', [AdminFacultadController::class, 'destroy'])->name('admin.facultad.destroy');

        // Tipos de Trabajo
        Route::get('/lista-tipo-trabajo', [AdminTipoTrabajoController::class, 'index'])->name('admin.listaTipoTrabajo');
        Route::post('/agregar-tipo-trabajo', [AdminTipoTrabajoController::class, 'store'])->name('admin.tipoTrabajo.store');
        Route::put('/tipo-trabajo/{id}', [AdminTipoTrabajoController::class, 'update'])->name('admin.tipoTrabajo.update');
        Route::delete('/eliminar-tipo-trabajo/{id}', [AdminTipoTrabajoController::class, 'destroy'])->name('admin.tipoTrabajo.destroy');
        Route::post('/tipo-trabajo/{id}/toggle', [AdminTipoTrabajoController::class, 'toggleActive'])->name('admin.tipoTrabajo.toggle');
    });

// --- MÓDULO GESTOR ---
Route::middleware(['auth', 'check.activo', 'check.role:Gestor'])
    ->prefix(config('app.gestor_prefix', 'gestor'))
    ->group(function () {
        Route::get('/', [GestorController::class, 'index'])->name('gestor.dashboard');
        Route::get('/lista-evaluadores', [GestorController::class, 'listaEvaluadores'])->name('gestor.listaEvaluadores');
        Route::get('/crear-trabajo', [GestorController::class, 'crearProyecto'])->name('gestor.crear');
        Route::post('/crear-trabajo', [TrabajoController::class, 'guardar'])->name('trabajo.guardar');
        Route::get('/trabajo/archivo/{id}', [TrabajoController::class, 'archivo'])->name('gestor.trabajo.archivo'); 
        Route::get('/trabajo/{id}/rubrica', [AsignarRubricaController::class, 'form'] )->name('gestor.rubrica.asignar');
        Route::post('/trabajo/{id}/rubrica', [AsignarRubricaController::class, 'store'])->name('gestor.rubrica.asignar.store');
        Route::get('/trabajo/{id}', [TrabajoController::class, 'detalles'])->name('gestor.trabajo.detalles');
        Route::post('/trabajo/{id}/actualizar-archivo', [TrabajoController::class, 'actualizarArchivo'])->name('gestor.trabajo.actualizarArchivo');
        Route::post('/trabajo/{id}/subir-nueva-version', [TrabajoController::class, 'subirNuevaVersion'])->name('gestor.trabajo.subirNuevaVersion');
        Route::post('/trabajo/{id}/retirar', [TrabajoController::class, 'retirar'])->name('gestor.trabajo.retirar');
        Route::get('/trabajo/{id}/subir-informe-final', [TrabajoController::class, 'subirInformeFinalForm'])->name('gestor.trabajo.informe-final');
        Route::post('/trabajo/{id}/subir-informe-final', [TrabajoController::class, 'subirInformeFinal'])->name('gestor.trabajo.informe-final.store');
        Route::post('/trabajo/{id}/decision-propuesta', [TrabajoController::class, 'decidirPropuesta'])->name('gestor.trabajo.decidirPropuesta');
        Route::delete('/trabajo/{id}/eliminar-rechazada', [TrabajoController::class, 'eliminarPropuestaRechazada'])->name('gestor.trabajo.eliminarRechazada');
    });

// --- MÓDULO EVALUADOR ---
Route::middleware(['auth', 'check.activo', 'check.role:Evaluador'])
    ->prefix(config('app.evaluador_prefix', 'evaluador'))
    ->group(function () {
        Route::get('/', [ControllerEvaluador::class, 'index'])->name('evaluador.dashboard');
        Route::get('/calificados', [ControllerEvaluador::class, 'trabajosCalificados'])->name('evaluador.calificados');
        Route::get('/evaluacion/{id}', function($id) {
            $usuario = auth()->user();
            $trabajo = \App\Models\Trabajo::with(['tipo', 'estudiante', 'evaluadores.usuario'])->findOrFail($id);
            
            $evaluacionPrevia = null;
            $miSlot = 1;
            
            if ($usuario->profesor) {
                $profesorId = $usuario->profesor->id_profesor;

                $pivot = \Illuminate\Support\Facades\DB::table('trabajo_profesor')
                    ->where('id_trabajo', $id)
                    ->where('id_profesor', $profesorId)
                    ->first();

                if (!$pivot || $pivot->decision_evaluador !== 'aceptado') {
                    return redirect()->route('evaluador.dashboard')
                        ->with('error', 'Debe aceptar el trabajo antes de evaluarlo.');
                }

                if (!$pivot->terminos_aceptados || !$pivot->datos_aceptados) {
                    return redirect()->route('evaluador.dashboard')
                        ->with('error', 'Debe aceptar los términos y condiciones antes de evaluar.');
                }

                $evaluacionPrevia = \App\Models\Evaluacion::where('id_trabajo', $id)
                    ->where('id_profesor', $profesorId)
                    ->first();
                
                $evaluadoresAsignados = \Illuminate\Support\Facades\DB::table('trabajo_profesor')
                    ->where('id_trabajo', $id)
                    ->orderBy('fecha_asignacion', 'asc')
                    ->orderBy('id_profesor', 'asc')
                    ->pluck('id_profesor')
                    ->toArray();
                
                $posicion = array_search($profesorId, $evaluadoresAsignados);
                $miSlot = ($posicion !== false) ? $posicion + 1 : 1;
            }
            
            return view('evaluador.evaluacion', compact('trabajo', 'evaluacionPrevia', 'miSlot'));
        })->name('evaluador.evaluacion.show');

        Route::get('/trabajo/archivo/{id}', [TrabajoController::class, 'archivo'])->name('trabajo.archivo');
        Route::get('/trabajos/{id}/rubrica', [ControllerEvaluador::class, 'getRubrica']);
        Route::post('/trabajos/{id}/guardar-evaluacion', [ControllerEvaluador::class, 'guardarEvaluacion'])->name('evaluador.guardar-evaluacion');
        Route::post('/trabajos/{id}/guardar-progreso', [ControllerEvaluador::class, 'guardarProgreso'])->name('evaluador.guardar-progreso');
        Route::post('/aceptar-terminos', [ControllerEvaluador::class, 'aceptarTerminos'])->name('evaluador.aceptar-terminos');
        Route::get('/evaluacion/{id}/detalles', [ControllerEvaluador::class, 'detallesEvaluacion'])->name('evaluador.detalles-evaluacion');
        Route::get('/evaluacion/{id}/rubrica-pdf', [ControllerEvaluador::class, 'rubricaPDF'])->name('evaluador.rubrica-pdf');
        Route::get('/revisar/{id}', [ControllerEvaluador::class, 'revisarTrabajo'])->name('evaluador.revisar-trabajo');
        Route::post('/aceptar/{id}', [ControllerEvaluador::class, 'aceptarTrabajo'])->name('evaluador.aceptar-trabajo');
        Route::post('/rechazar/{id}', [ControllerEvaluador::class, 'rechazarTrabajo'])->name('evaluador.rechazar-trabajo');
    });
