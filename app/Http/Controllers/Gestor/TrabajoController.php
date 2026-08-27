<?php

namespace App\Http\Controllers\Gestor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trabajo;
use App\Models\TipoTrabajo;
use App\Models\Estudiante;
use App\Models\Rubrica;
use App\Models\Usuario;
use App\Models\Director;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\TrabajoSubidoEstudiante;
use App\Models\Retroalimentacion;
use App\Notifications\NuevoTrabajoSubido;
use App\Notifications\NuevaVersionDisponible;
use App\Notifications\TrabajoRetirado;
use App\Notifications\TrabajoReactivado;
use App\Notifications\TrabajoRetiradoEvaluador;
use App\Notifications\InformeFinalSubido;
use OpenApi\Attributes as OA;

/** Controlador para la gestión de trabajos de grado por parte del gestor. */
class TrabajoController extends Controller
{
    // Mostrar el formulario para crear un nuevo trabajo
    public function crear()
    {
        $tipos = TipoTrabajo::all(); // Obtener tipos de trabajo de la BD
        $rubricas = Rubrica::where('activo', true)->with('tipo')->get();
        $usuario = Auth::user();      // Para pasar nombre del gestor
        return view('gestor.creartrabajo', compact('tipos', 'usuario', 'rubricas'));
    }

    // Mostrar o descargar archivo PDF
    public function archivo($id)
    {
        $trabajo = \App\Models\Trabajo::find($id);

        if (!$trabajo) {
            abort(404, 'Trabajo de grado no encontrado.');
        }

        $path = null;

        if (!empty($trabajo->archivo_pdf)) {
            // Limpiar prefijos comunes como 'storage/', '/storage/', 'public/', '/'
            $relative = preg_replace('#^/?(storage|public)/?#i', '', $trabajo->archivo_pdf);
            $relative = ltrim($relative, '/\\');

            if (Storage::disk('public')->exists($relative)) {
                $path = Storage::disk('public')->path($relative);
            } elseif (file_exists(public_path($trabajo->archivo_pdf))) {
                $path = public_path($trabajo->archivo_pdf);
            } elseif (file_exists(public_path('storage/' . $relative))) {
                $path = public_path('storage/' . $relative);
            } elseif (file_exists(storage_path('app/public/' . $relative))) {
                $path = storage_path('app/public/' . $relative);
            } elseif (file_exists(storage_path('app/' . $relative))) {
                $path = storage_path('app/' . $relative);
            } elseif (file_exists(base_path($trabajo->archivo_pdf))) {
                $path = base_path($trabajo->archivo_pdf);
            }
        }

        // Fallback para entornos Serverless (como Vercel) si el archivo específico no está en disco:
        // Buscar cualquier PDF de muestra en storage/app/public/pdf/ o storage/app/public/actas/
        if (!$path || !file_exists($path)) {
            $fallbackFiles = glob(storage_path('app/public/pdf/*.pdf'));
            if (!empty($fallbackFiles)) {
                $path = $fallbackFiles[0];
            } else {
                $fallbackActas = glob(storage_path('app/public/actas/*.pdf'));
                if (!empty($fallbackActas)) {
                    $path = $fallbackActas[0];
                }
            }
        }

        if (!$path || !file_exists($path)) {
            abort(404, 'El archivo PDF no fue encontrado en el servidor.');
        }

        $filename = !empty($trabajo->archivo_pdf) ? basename($trabajo->archivo_pdf) : 'documento.pdf';
        $content = file_get_contents($path);
        $isDownload = request()->has('download') || request()->get('disposition') === 'attachment';
        $disposition = $isDownload ? 'attachment' : 'inline';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            'Content-Length' => strlen($content),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    // Guardar el trabajo en la base de datos
    public function guardar(Request $request)
    {
        $maxEstudiantes = $request->plantilla_rubrica === 'pasantia' ? 1 : 3;

        $request->validate([
            'titulo'              => 'required|string|max:255',
            'id_tipo'             => 'required|exists:tipo_trabajo,id_tipo',
            'plantilla_rubrica'   => 'required|in:propuesta_de_grado,pasantia',
            'archivo_pdf'         => 'required|mimes:pdf|mimetypes:application/pdf|max:51200', // Máx 50MB
            'estudiantes'         => 'required|array|min:1|max:' . $maxEstudiantes,
            'estudiantes.*.nombre'    => 'required|string|max:100',
            'estudiantes.*.apellido'  => 'required|string|max:100',
            'estudiantes.*.correo'    => 'nullable|email|max:100',
            'estudiantes.*.id_area'   => 'required|exists:area,id_area',
            'director.nombre'     => 'required|string|max:100',
            'director.apellido'   => 'required|string|max:100',
            'director.correo'     => 'required|email|max:100',
            'subdirector.nombre'  => 'nullable|required_with:subdirector.apellido,subdirector.correo|string|max:100',
            'subdirector.apellido'=> 'nullable|required_with:subdirector.nombre,subdirector.correo|string|max:100',
            'subdirector.correo'  => 'nullable|required_with:subdirector.nombre,subdirector.apellido|email|max:100',
        ]);

        // **Verificar que cada estudiante no esté asignado ya a un trabajo**
        foreach ($request->estudiantes as $estudiante) {
            $existe = Estudiante::where('nombre', $estudiante['nombre'])
                ->where('apellido', $estudiante['apellido'])
                ->where('id_area', $estudiante['id_area'])
                ->exists();

            if ($existe) {
                return redirect()->back()
                    ->with('error', "El estudiante {$estudiante['nombre']} {$estudiante['apellido']} ya está asignado a un trabajo de grado.")
                    ->withInput();
            }
        }

        // 🔹 Generar el código del proyecto ANTES de guardar el archivo para
        //    poder nombrar el PDF con el prefijo (trazabilidad).
        $codigoProyecto = Trabajo::generarCodigoProyecto();

        // 🔹 Guardar el archivo PDF con el código del proyecto como prefijo
        $archivo = $request->file('archivo_pdf');
        $nombreArchivo = $this->nombreArchivoConCodigo($codigoProyecto, $archivo);

        $rutaArchivo = $archivo->storeAs('pdf', $nombreArchivo, 'public');

        // Guardar todo en una transacción: trabajo, historial, estudiantes, rúbrica y pivote
        DB::beginTransaction();
        try {
            $trabajo = Trabajo::create([
                'codigo_proyecto'   => $codigoProyecto,
                'titulo'            => $request->titulo,
                'fecha_subida'      => now()->toDateString(),
                'id_tipo'           => $request->id_tipo,
                'plantilla_rubrica' => $request->plantilla_rubrica,
                'archivo_pdf'       => 'storage/' . $rutaArchivo,
                'estado'            => 'subido',
            ]);

            // Crear el registro de historial_estados inicial
            \App\Models\HistorialEstado::create([
                'trabajo_grado_id' => $trabajo->id_trabajo,
                'estado' => 'subido',
                'user_id' => Auth::id(),
                'observacion_estado' => 'Documento inicial subido al sistema.',
            ]);

            // Guardar los estudiantes relacionados
            foreach ($request->estudiantes as $estudiante) {
                Estudiante::create([
                    'id_trabajo' => $trabajo->id_trabajo,
                    'nombre' => $estudiante['nombre'],
                    'apellido' => $estudiante['apellido'],
                    'correo' => $estudiante['correo'] ?? null,
                    'id_area' => $estudiante['id_area'],
                ]);
            }

            // Guardar o buscar Director
            $director = Director::firstOrCreate(
                ['correo_electronico' => $request->director['correo']],
                [
                    'nombre' => $request->director['nombre'],
                    'apellido' => $request->director['apellido'],
                ]
            );
            $trabajo->directores()->attach($director->id_director, ['rol' => 'director']);

            // Guardar o buscar Subdirector (si se ingresó)
            if (!empty($request->subdirector['nombre']) && !empty($request->subdirector['correo'])) {
                $subdirector = Director::firstOrCreate(
                    ['correo_electronico' => $request->subdirector['correo']],
                    [
                        'nombre' => $request->subdirector['nombre'],
                        'apellido' => $request->subdirector['apellido'],
                    ]
                );
                $trabajo->directores()->attach($subdirector->id_director, ['rol' => 'subdirector']);
            }

            // Vincular la rúbrica existente con el trabajo (si se proporcionó)
            if ($request->filled('id_rubrica')) {
                $trabajo->rubricas()->attach($request->id_rubrica, ['fecha_asignacion' => now()]);
            }

            DB::commit();

            // ── Notificar a todos los Administradores ──
            $nombreGestor = Auth::user()->nombre . ' ' . Auth::user()->apellido;
            $admins = Usuario::where('rol', 'Administrador')->where('activo', true)->get();
            foreach ($admins as $admin) {
                $admin->notify(new NuevoTrabajoSubido($trabajo, $nombreGestor));
            }

            // ── Notificar por Correo Electrónico a Estudiantes y Director ──
            try {
                $trabajo->load(['estudiante', 'directores', 'tipo']);

                // Correo a cada estudiante si tiene email
                foreach ($trabajo->estudiante as $est) {
                    if (!empty($est->correo)) {
                        Mail::to($est->correo)->send(new \App\Mail\PropuestaSubidaNotificacion(
                            $trabajo,
                            $est->nombre . ' ' . $est->apellido,
                            'Estudiante'
                        ));
                    }
                }

                // Correo al Director y Subdirector
                foreach ($trabajo->directores as $dir) {
                    if (!empty($dir->correo_electronico)) {
                        Mail::to($dir->correo_electronico)->send(new \App\Mail\PropuestaSubidaNotificacion(
                            $trabajo,
                            $dir->nombre . ' ' . $dir->apellido,
                            'Director'
                        ));
                    }
                }
            } catch (\Throwable $e) {
                \Log::error('Error enviando notificaciones por correo de propuesta subida: ' . $e->getMessage());
            }

             return redirect()->route('gestor.dashboard')->with('success', 'Trabajo y estudiantes guardados correctamente. Se han enviado las notificaciones por correo.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Limpiar archivo subido si existiera
            if (isset($rutaArchivo) && Storage::disk('public')->exists($rutaArchivo)) {
                Storage::disk('public')->delete($rutaArchivo);
            }

            return redirect()->back()->with('error', 'Ocurrió un error al guardar el trabajo: ' . $e->getMessage());
        }
    }

    public function detalles($id)
    {
        $usuario = Auth::user();
        $trabajo = Trabajo::with([
            'estudiante.area',
            'estudiante.area.facultad',
            'tipo',
            'directores',
            'evaluadores' => fn($q) => $q->withPivot('estado_revision'),
            'evaluadores.usuario',
            'evaluaciones.profesor.usuario',
        ])->findOrFail($id);
        return view('gestor.detallesTrabajo', compact('usuario', 'trabajo'));
    }

    public function actualizarArchivo(Request $request, $id)
    {
        $request->validate([
            'archivo_pdf' => 'required|mimes:pdf|mimetypes:application/pdf|max:51200',
        ]);

        $trabajo = Trabajo::findOrFail($id);

        // Eliminar archivo anterior
        $relative = preg_replace('#^storage/#', '', $trabajo->archivo_pdf);
        if (Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }

        // Eliminar el documento anterior si existe para evitar duplicados
        if ($trabajo->archivo_pdf) {
            $oldPath = str_replace('storage/', '', $trabajo->archivo_pdf);
            if (\Storage::disk('public')->exists($oldPath)) {
                \Storage::disk('public')->delete($oldPath);
            }
        }

        // Guardar el nuevo archivo con el código del proyecto como prefijo
        $archivo = $request->file('archivo_pdf');
        $nombreArchivo = $this->nombreArchivoConCodigo($trabajo->codigo_proyecto, $archivo);

        $rutaArchivo = $archivo->storeAs('pdf', $nombreArchivo, 'public');
        $trabajo->update(['archivo_pdf' => 'storage/' . $rutaArchivo]);

        // Borrar retroalimentaciones anteriores al reemplazar el documento
        Retroalimentacion::where('trabajo_grado_id', $trabajo->id_trabajo)->delete();

        return redirect()->route('gestor.trabajo.detalles', $id)
            ->with('success', 'Archivo PDF actualizado correctamente.');
    }

    // Subir el documento corregido del trabajo de grado
    public function subirNuevaVersion(Request $request, $id)
    {
        $request->validate([
            'archivo_pdf' => 'required|mimes:pdf|mimetypes:application/pdf|max:51200', // Máx 50MB
            'observacion_estado' => 'nullable|string|max:1000',
        ]);

        $trabajo = Trabajo::with('evaluaciones')->findOrFail($id);

        // Eliminar el documento anterior si existe para evitar duplicados
        if ($trabajo->archivo_pdf) {
            $oldPath = str_replace('storage/', '', $trabajo->archivo_pdf);
            if (\Storage::disk('public')->exists($oldPath)) {
                \Storage::disk('public')->delete($oldPath);
            }
        }

        // Guardar el nuevo archivo corregido con el código del proyecto como prefijo
        $archivo = $request->file('archivo_pdf');
        $nombreArchivo = $this->nombreArchivoConCodigo($trabajo->codigo_proyecto, $archivo);

        $rutaArchivo = $archivo->storeAs('pdf', $nombreArchivo, 'public');

        // Actualizar el modelo del trabajo con el nuevo archivo
        $trabajo->update([
            'archivo_pdf' => 'storage/' . $rutaArchivo,
            'estado' => 'version_corregida_subida',
        ]);

        $nuevaFechaLimite = \Carbon\Carbon::now()->addWeekdays(15)->toDateString();
        $notificarA = [];

        // Evaluar quién requiere re-revisión según su evaluación anterior
        $trabajo->load(['evaluaciones', 'evaluadores']);

        foreach ($trabajo->evaluadores as $evaluador) {
            $eval = $trabajo->evaluaciones->where('id_profesor', $evaluador->id_profesor)->first();
            // Si el evaluador no ha evaluado aún o su evaluación anterior no fue aceptada ("Aceptada" o "Puede Sustentar")
            if (!$eval || !in_array(strtolower(trim($eval->resultado ?? '')), ['aceptada', 'puede_sustentar'])) {
                DB::table('trabajo_profesor')
                    ->where('id_trabajo', $trabajo->id_trabajo)
                    ->where('id_profesor', $evaluador->id_profesor)
                    ->update([
                        'estado_revision' => 'Asignado',
                        'requiere_nueva_revision' => true,
                        'fecha_limite_revision' => $nuevaFechaLimite,
                    ]);

                if ($eval) {
                    $eval->update([
                        'evaluacion_completada' => false,
                        'firma' => null,
                    ]);
                }

                $notificarA[] = $evaluador->id_profesor;
            }
        }

        // Crear registro en historial_estados
        \App\Models\HistorialEstado::create([
            'trabajo_grado_id' => $trabajo->id_trabajo,
            'estado' => 'version_corregida_subida',
            'user_id' => Auth::id(),
            'observacion_estado' => $request->observacion_estado ?? 'Documento corregido subido por el gestor.',
        ]);

        // ── Notificar a evaluadores que requieren re-revisión ──
        try {
            $trabajo->load('evaluadores.usuario');
            foreach ($trabajo->evaluadores as $evaluador) {
                if (in_array($evaluador->id_profesor, $notificarA) && $evaluador->usuario) {
                    $evaluador->usuario->notify(new NuevaVersionDisponible($trabajo));
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Error al notificar evaluadores: ' . $e->getMessage());
        }

        // ── Notificar al Admin ──
        try {
            $admins = Usuario::where('rol', 'Administrador')->where('activo', true)->get();
            foreach ($admins as $admin) {
                $admin->notify(new NuevaVersionDisponible($trabajo));
            }
        } catch (\Throwable $e) {
            \Log::error('Error al notificar admin: ' . $e->getMessage());
        }

        return redirect()->route('gestor.trabajo.detalles', $id)
            ->with('success', 'Documento corregido cargado correctamente.');
    }

    public function retirar($id)
    {
        if (!auth()->check() || auth()->user()->rol !== 'Administrador') {
            abort(403, 'Solo el administrador puede retirar o reactivar trabajos.');
        }

        $trabajo = Trabajo::findOrFail($id);

        if ($trabajo->plantilla_rubrica !== 'propuesta_de_grado') {
            return redirect()->back()->with('error', 'No es posible retirar un proyecto que ya fue convertido a Trabajo de Grado.');
        }

        $retirado = !$trabajo->retirado;
        $trabajo->update(['retirado' => $retirado]);

        \App\Models\HistorialEstado::create([
            'trabajo_grado_id' => $trabajo->id_trabajo,
            'estado' => $trabajo->estado,
            'user_id' => Auth::id(),
            'observacion_estado' => $retirado
                ? 'Proyecto retirado por el gestor.'
                : 'Proyecto reactivado por el gestor.',
        ]);

        // ── Notificar a todos los Administradores ──
        $nombreActor = 'El gestor ' . Auth::user()->nombre . ' ' . Auth::user()->apellido;
        $admins = Usuario::where('rol', 'Administrador')->where('activo', true)->get();
        foreach ($admins as $admin) {
            if ($retirado) {
                $admin->notify(new TrabajoRetirado($trabajo, $nombreActor));
            } else {
                $admin->notify(new TrabajoReactivado($trabajo, $nombreActor));
            }
        }

        // ── Notificar a los evaluadores asignados si el proyecto fue retirado ──
        if ($retirado) {
            $evaluadores = $trabajo->evaluadores;
            foreach ($evaluadores as $evaluador) {
                if ($evaluador->usuario) {
                    $evaluador->usuario->notify(new TrabajoRetiradoEvaluador($trabajo, $nombreActor));
                }
            }
        }

        $mensaje = $retirado
            ? 'Proyecto retirado correctamente.'
            : 'Proyecto reactivado correctamente.';

        return redirect()->route('gestor.trabajo.detalles', $id)
            ->with('success', $mensaje);
    }

    public function subirInformeFinalForm($id)
    {
        $usuario = Auth::user();
        $trabajo = Trabajo::with(['evaluadores' => function ($q) {
            $q->withPivot('estado_revision');
        }, 'tipo', 'estudiante'])->findOrFail($id);

        // Verificar que sea una propuesta
        if ($trabajo->plantilla_rubrica !== 'propuesta_de_grado') {
            return redirect()->route('gestor.dashboard')->with('error', 'Este trabajo no es una propuesta de grado.');
        }

        // Verificar que al menos un evaluador haya finalizado
        if ($trabajo->evaluadores->isEmpty() || !$trabajo->evaluadores->contains(fn($e) => $e->pivot->estado_revision === 'Finalizado')) {
            return redirect()->route('gestor.dashboard')->with('error', 'Aún ningún evaluador ha finalizado su evaluación.');
        }

        // Bloquear informe final si la propuesta fue rechazada
        $evaluacion = $trabajo->evaluaciones->first();
        if ($evaluacion && ($evaluacion->resultado ?? null) === 'rechazada') {
            return redirect()->route('gestor.trabajo.detalles', $trabajo->id_trabajo)
                ->with('error', 'No es posible subir el informe final: la propuesta fue rechazada.');
        }

        return view('gestor.subirInformeFinal', compact('usuario', 'trabajo'));
    }

    public function subirInformeFinal(Request $request, $id)
    {
        $request->validate([
            'archivo_pdf' => 'required|mimes:pdf|mimetypes:application/pdf|max:51200',
        ]);

        $trabajo = Trabajo::with(['evaluadores' => function ($q) {
            $q->withPivot('estado_revision');
        }])->findOrFail($id);

        // Verificar que sea una propuesta
        if ($trabajo->plantilla_rubrica !== 'propuesta_de_grado') {
            return redirect()->route('gestor.dashboard')->with('error', 'Este trabajo no es una propuesta de grado.');
        }

        // Verificar que al menos un evaluador haya finalizado
        if ($trabajo->evaluadores->isEmpty() || !$trabajo->evaluadores->contains(fn($e) => $e->pivot->estado_revision === 'Finalizado')) {
            return redirect()->route('gestor.dashboard')->with('error', 'Aún ningún evaluador ha finalizado su evaluación.');
        }

        // Bloquear informe final si la propuesta fue rechazada
        $evaluacion = $trabajo->evaluaciones->first();
        if ($evaluacion && ($evaluacion->resultado ?? null) === 'rechazada') {
            return redirect()->route('gestor.trabajo.detalles', $trabajo->id_trabajo)
                ->with('error', 'No es posible subir el informe final: la propuesta fue rechazada.');
        }

        // Obtener el ID del tipo "Trabajo de Grado"
        $tipoTG = TipoTrabajo::where('nombre_tipo', 'ILIKE', '%Trabajo de Grado%')->first();
        if (!$tipoTG) {
            return redirect()->back()->with('error', 'No se encontró el tipo "Trabajo de Grado". Contacta al administrador.');
        }

        DB::beginTransaction();
        try {
            // Eliminar el documento anterior si existe para evitar duplicados
            if ($trabajo->archivo_pdf) {
                $oldPath = str_replace('storage/', '', $trabajo->archivo_pdf);
                if (\Storage::disk('public')->exists($oldPath)) {
                    \Storage::disk('public')->delete($oldPath);
                }
            }

            // Guardar el nuevo archivo con el código del proyecto como prefijo
            $archivo = $request->file('archivo_pdf');
            $nombreArchivo = $this->nombreArchivoConCodigo($trabajo->codigo_proyecto, $archivo);

            $rutaArchivo = $archivo->storeAs('pdf', $nombreArchivo, 'public');

            // Convertir propuesta a trabajo de grado
            $trabajo->update([
                'archivo_pdf'       => 'storage/' . $rutaArchivo,
                'id_tipo'           => $tipoTG->id_tipo,
                'plantilla_rubrica' => 'trabajo_de_grado',
                'estado'            => 'subido',
            ]);

            // Registrar en historial
            \App\Models\HistorialEstado::create([
                'trabajo_grado_id' => $trabajo->id_trabajo,
                'estado' => 'subido',
                'user_id' => Auth::id(),
                'observacion_estado' => 'Informe final subido. La propuesta ha sido convertida a Trabajo de Grado.',
            ]);

            // Resetear estado_revision Y fecha_limite_revision de todos los evaluadores
            // para que el nuevo plazo de 15 días hábiles empiece desde hoy
            $nuevaFechaLimite = \Carbon\Carbon::now()->addWeekdays(15)->toDateString();
            DB::table('trabajo_profesor')
                ->where('id_trabajo', $trabajo->id_trabajo)
                ->update([
                    'estado_revision'      => 'Asignado',
                    'fecha_limite_revision' => $nuevaFechaLimite,
                ]);

            DB::commit();

            // Notificar a los evaluadores
            try {
                $trabajo->load('evaluadores.usuario');
                foreach ($trabajo->evaluadores as $evaluador) {
                    if ($evaluador->usuario) {
                        $evaluador->usuario->notify(new InformeFinalSubido($trabajo));
                    }
                }
            } catch (\Throwable $e) {
                \Log::error('Error al notificar evaluadores: ' . $e->getMessage());
            }

            // Notificar a los administradores
            try {
                $admins = Usuario::where('rol', 'Administrador')->where('activo', true)->get();
                foreach ($admins as $admin) {
                    $admin->notify(new InformeFinalSubido($trabajo));
                }
            } catch (\Throwable $e) {
                \Log::error('Error al notificar admins: ' . $e->getMessage());
            }

            return redirect()->route('gestor.dashboard')
                ->with('success', 'Informe final subido correctamente. El trabajo ahora es un Trabajo de Grado y se ha reasignado a los evaluadores.');
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($rutaArchivo) && Storage::disk('public')->exists($rutaArchivo)) {
                Storage::disk('public')->delete($rutaArchivo);
            }
            return redirect()->back()->with('error', 'Error al subir el informe final: ' . $e->getMessage());
        }
    }

    /**
     * Maneja la decisión de los estudiantes (a través del gestor) cuando una
     * propuesta de grado fue rechazada:
     *  - SÍ: se sube una nueva propuesta y se reinicia el ciclo de evaluación.
     *  - NO: el proceso finaliza y la propuesta queda marcada como Rechazada.
     */
    public function decidirPropuesta(Request $request, $id)
    {
        $trabajo = Trabajo::with('evaluaciones')->findOrFail($id);

        // Verificar que sea una propuesta
        if ($trabajo->plantilla_rubrica !== 'propuesta_de_grado') {
            return redirect()->route('gestor.trabajo.detalles', $id)
                ->with('error', 'Este trabajo no es una propuesta de grado.');
        }

        // Verificar que el proceso no esté ya finalizado
        if ($trabajo->estado === 'rechazada') {
            return redirect()->route('gestor.trabajo.detalles', $id)
                ->with('error', 'El proceso de esta propuesta ya fue finalizado.');
        }

        // Verificar que la propuesta esté rechazada por todos los evaluadores
        $ambosRechazan = $trabajo->evaluaciones->count() > 0 && $trabajo->evaluaciones->every(function ($eval) {
            return strtolower(trim($eval->resultado)) === 'rechazada';
        });

        if (!$ambosRechazan) {
            return redirect()->route('gestor.trabajo.detalles', $id)
                ->with('error', 'Esta propuesta no está en estado de rechazo unánime.');
        }

        $request->validate([
            'decision' => 'required|in:si,no',
            'archivo_pdf' => 'required_if:decision,si|mimes:pdf|max:51200',
        ]);

        // ── Caso NO: los estudiantes no desean continuar ──
        if ($request->decision === 'no') {
            $trabajo->update(['estado' => 'rechazada']);

            \App\Models\HistorialEstado::create([
                'trabajo_grado_id' => $trabajo->id_trabajo,
                'estado' => 'propuesta_rechazada',
                'user_id' => Auth::id(),
                'observacion_estado' => 'Los estudiantes no desean continuar con el proyecto. Proceso finalizado.',
            ]);

            return redirect()->route('gestor.trabajo.detalles', $id)
                ->with('success', 'Proceso finalizado. La propuesta ha quedado marcada como Rechazada.');
        }

        // ── Caso SÍ: subir la nueva propuesta con el código del proyecto como prefijo ──
        $archivo = $request->file('archivo_pdf');
        $nombreArchivo = $this->nombreArchivoConCodigo($trabajo->codigo_proyecto, $archivo);

        $rutaArchivo = $archivo->storeAs('pdf', $nombreArchivo, 'public');

        DB::beginTransaction();
        try {
            $trabajo->update([
                'archivo_pdf' => 'storage/' . $rutaArchivo,
                'estado' => 'subido',
            ]);

            // Resetear el ciclo de evaluación en el pivote
            $nuevaFechaLimite = \Carbon\Carbon::now()->addWeekdays(15)->toDateString();
            DB::table('trabajo_profesor')
                ->where('id_trabajo', $trabajo->id_trabajo)
                ->update([
                    'estado_revision' => 'Asignado',
                    'decision_evaluador' => null,
                    'motivo_rechazo' => null,
                    'fecha_limite_revision' => $nuevaFechaLimite,
                ]);

            // Resetear TODAS las evaluaciones del ciclo anterior
            foreach ($trabajo->evaluaciones as $eval) {
                $eval->update([
                    'nota_final' => null,
                    'resultado' => null,
                    'criterios' => [],
                    'observaciones_globales' => null,
                    'firma' => null,
                    'firma_evaluador_2' => null,
                    'evaluacion_completada' => false,
                ]);
            }

            // Registrar en historial
            \App\Models\HistorialEstado::create([
                'trabajo_grado_id' => $trabajo->id_trabajo,
                'estado' => 'nueva_propuesta_subida',
                'user_id' => Auth::id(),
                'observacion_estado' => 'Los estudiantes desean continuar. Nueva propuesta subida; ciclo de evaluación reiniciado.',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if (Storage::disk('public')->exists($rutaArchivo)) {
                Storage::disk('public')->delete($rutaArchivo);
            }
            return redirect()->back()->with('error', 'Error al subir la nueva propuesta: ' . $e->getMessage());
        }

        // ── Notificar a los evaluadores asignados ──
        try {
            $trabajo->load('evaluadores.usuario');
            foreach ($trabajo->evaluadores as $evaluador) {
                if ($evaluador->usuario) {
                    $evaluador->usuario->notify(new NuevaVersionDisponible($trabajo));
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Error al notificar evaluadores: ' . $e->getMessage());
        }

        return redirect()->route('gestor.trabajo.detalles', $id)
            ->with('success', 'Nueva propuesta subida. Los evaluadores han sido notificados para reiniciar la revisión.');
    }

    /**
     * Elimina directamente una propuesta cuyo proceso fue rechazado (los
     * estudiantes no desean continuar con el proyecto).
     */
    public function eliminarPropuestaRechazada($id)
    {
        $trabajo = Trabajo::findOrFail($id);

        // Solo se permite eliminar directamente las propuestas marcadas como Rechazadas
        if ($trabajo->estado !== 'rechazada') {
            return redirect()->route('gestor.trabajo.detalles', $id)
                ->with('error', 'Solo es posible eliminar directamente las propuestas marcadas como Rechazadas.');
        }

        DB::transaction(function () use ($trabajo) {
            // Desvincular relaciones pivote
            $trabajo->evaluadores()->detach();
            $trabajo->rubricas()->detach();
            $trabajo->directores()->detach();

            // Eliminar registros relacionados
            DB::table('trabajo_estudiante')->where('id_trabajo', $trabajo->id_trabajo)->delete();
            DB::table('alerta')->where('id_trabajo', $trabajo->id_trabajo)->delete();
            DB::table('seguimiento')->where('id_trabajo', $trabajo->id_trabajo)->delete();

            $trabajo->retroalimentaciones()->delete();
            $trabajo->historialEstados()->delete();
            $trabajo->evaluaciones()->delete();

            // Eliminar estudiantes
            DB::table('estudiante')->where('id_trabajo', $trabajo->id_trabajo)->delete();

            // Eliminar el trabajo (el observer borra el PDF del almacenamiento)
            $trabajo->delete();
        });

        return redirect()->route('gestor.dashboard')
            ->with('success', 'Propuesta rechazada eliminada correctamente.');
    }

    /**
     * Construye el nombre del archivo PDF anteponiendo el código del proyecto
     * para trazabilidad (formato: PGTG-001-26-nombre-del-documento.pdf).
     * Si el trabajo no tiene código, usa el nombre limpio sin prefijo.
     */
    private function nombreArchivoConCodigo(?string $codigo, $archivo): string
    {
        $nombreLimpio = Str::slug(pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = $archivo->getClientOriginalExtension();

        $base = $codigo
            ? $codigo . '-' . $nombreLimpio
            : $nombreLimpio;

        $nombreArchivo = $base . '.' . $extension;

        // Si ya existe un archivo con ese nombre, agregarle un sufijo numérico
        $contador = 1;
        while (Storage::disk('public')->exists("pdf/{$nombreArchivo}")) {
            $nombreArchivo = "{$base}-{$contador}.{$extension}";
            $contador++;
        }

        return $nombreArchivo;
    }
}
