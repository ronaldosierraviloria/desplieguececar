@extends('layouts.baseGestor')

@section('title', 'Detalles del Proyecto | Panel Gestor')
@section('meta_description', 'Vista de administrador de los detalles de un trabajo de grado: estado, evaluadores, documentos y rubricas.')

@section('content')
@php
$hasStatus = session()->has('success') || session()->has('error');
$tipo_nombre = optional($trabajo->tipo)->nombre_tipo ?? 'Sin tipo';
$esPropuesta = $trabajo->plantilla_rubrica === 'propuesta_de_grado';
$todosEvalFinalizados = $trabajo->evaluadores->isNotEmpty() && $trabajo->evaluadores->every(fn($e) => $e->pivot->estado_revision === 'Finalizado' && empty($e->pivot->requiere_nueva_revision));
$algunoFinalizado = $trabajo->evaluadores->contains(fn($e) => $e->pivot->estado_revision === 'Finalizado' && empty($e->pivot->requiere_nueva_revision));

// Buscar si algún evaluador rechazó o solicitó mejoras
$algunaMejoraRechazo = $trabajo->evaluaciones->contains(function ($eval) {
    return !in_array(strtolower(trim($eval->resultado ?? '')), ['aceptada', 'puede_sustentar']);
});

// Verificar si AMBOS (todos los evaluadores) rechazaron
$ambosRechazan = $trabajo->evaluaciones->count() > 0 && $trabajo->evaluaciones->every(function ($eval) {
    return in_array(strtolower(trim($eval->resultado ?? '')), ['rechazada', 'no_sustentar', 'requiere_reestructurar']);
});

$propuestaRechazada = $esPropuesta && $todosEvalFinalizados && $ambosRechazan && ($trabajo->estado ?? null) !== 'rechazada';
$requiereNuevaVersion = $todosEvalFinalizados && !$ambosRechazan && $algunaMejoraRechazo && ($trabajo->estado ?? null) !== 'rechazada';
$puedeSubirInformeFinal = $todosEvalFinalizados && $esPropuesta && !$algunaMejoraRechazo && ($trabajo->estado ?? null) !== 'rechazada';

// Acta de sustentación: Solo aparece cuando ambos evaluadores calificaron 'PUEDE SUSTENTAR' (o si ya se subió)
$ambosPuedeSustentar = $todosEvalFinalizados 
    && $trabajo->evaluaciones->count() >= 2 
    && $trabajo->evaluaciones->every(function ($eval) {
        return strtolower(trim($eval->resultado ?? '')) === 'puede_sustentar';
    });

$mostrarActaSustentacion = ($trabajo->plantilla_rubrica === 'trabajo_de_grado') 
    && ($trabajo->tieneActaSustentacion() || $ambosPuedeSustentar);
@endphp
<div class="max-w-7xl mx-auto" x-data="{
    showModal: @json($hasStatus),
    dragOver: false,
    actaFile: null,
    actaFileName: '',
    isSubmitting: false,
    isDeleting: false,
    estudianteCount: {{ $trabajo->estudiante->count() }},
    showDeleteModal: false,
    motivoEliminacion: '',
    studentToDelete: null,
    showRestrictionModal: false,
    isProrrogando: false,
    showProrrogaModal: false,
    prorrogaDias: 21,
    evaluadorProrroga: {},
    showSuccessModal: false,
    successMessage: '',
    actaHighlight: false,
    init() {
        const params = new URLSearchParams(window.location.search);
        if (params.get('ir') === 'acta') {
            this.$nextTick(() => {
                const card = document.getElementById('card-acta');
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    this.actaHighlight = true;
                    setTimeout(() => { this.actaHighlight = false; }, 6000);
                }
            });
            params.delete('ir');
            const qs = params.toString();
            window.history.replaceState({}, '', window.location.pathname + (qs ? '?' + qs : ''));
        }
    },
    handleActaChange(e) {
        const f = (e.target.files && e.target.files[0]) ? e.target.files[0] : null;
        if (f) {
            this.actaFile = f;
            this.actaFileName = f.name;
        } else {
            this.actaFile = null;
            this.actaFileName = '';
        }
    },
    handleActaDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        const files = (e.dataTransfer && e.dataTransfer.files) ? e.dataTransfer.files : (e.target.files || null);
        if (files && files.length) {
            const f = files[0];
            try {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(f);
                if (this.$refs.fileInput) this.$refs.fileInput.files = dataTransfer.files;
            } catch (err) {}
            this.actaFile = f;
            this.actaFileName = f.name;
            this.dragOver = false;
        }
    },
    removeActa() {
        this.actaFile = null;
        this.actaFileName = '';
        if (this.$refs.fileInput) {
            this.$refs.fileInput.value = null;
        }
    },
    triggerFileDialog() {
        if (this.$refs.fileInput) this.$refs.fileInput.click();
    },
    // ── Estado del formulario: Acta de Sustentación ──
    dragOverSus: false,
    actaSusFile: null,
    actaSusFileName: '',
    isSubmittingSus: false,
    actaSusReemplazo: false,
    handleActaSusChange(e) {
        const f = (e.target.files && e.target.files[0]) ? e.target.files[0] : null;
        if (f) {
            this.actaSusFile = f;
            this.actaSusFileName = f.name;
        } else {
            this.actaSusFile = null;
            this.actaSusFileName = '';
        }
    },
    handleActaSusDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        const files = (e.dataTransfer && e.dataTransfer.files) ? e.dataTransfer.files : (e.target.files || null);
        if (files && files.length) {
            const f = files[0];
            try {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(f);
                if (this.$refs.actaSusFileInput) this.$refs.actaSusFileInput.files = dataTransfer.files;
            } catch (err) {}
            this.actaSusFile = f;
            this.actaSusFileName = f.name;
            this.dragOverSus = false;
        }
    },
    removeActaSus() {
        this.actaSusFile = null;
        this.actaSusFileName = '';
        if (this.$refs.actaSusFileInput) {
            this.$refs.actaSusFileInput.value = null;
        }
    },
    triggerActaSusDialog() {
        if (this.$refs.actaSusFileInput) this.$refs.actaSusFileInput.click();
    },
    deleteStudent(idEstudiante) {
        this.isDeleting = true;
        fetch(`/admin/estudiante/eliminar/${idEstudiante}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ motivo: this.motivoEliminacion })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById(`student-row-${idEstudiante}`);
                if (row) {
                    row.style.opacity = 0;
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => row.remove(), 300);
                }
                this.estudianteCount--;
                this.showDeleteModal = false;
                this.motivoEliminacion = '';
            } else {
                alert('Error: ' + (data.message || 'No se pudo eliminar al estudiante.'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error al intentar eliminar al estudiante.');
        })
        .finally(() => { this.isDeleting = false; });
    },
    prorrogarPlazo(idProfesor, dias) {
        this.isProrrogando = true;
        fetch('/admin/trabajo-evaluador/prorrogar', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                id_trabajo: '{{ $trabajo->id_trabajo }}',
                id_profesor: idProfesor,
                dias: dias
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const dateEl = document.getElementById(`deadline-date-${idProfesor}`);
                if (dateEl) dateEl.textContent = data.nueva_fecha;
                const statusEl = document.getElementById(`deadline-status-container-${idProfesor}`);
                if (statusEl) {
                    let html = '';
                    if (data.dias_restantes > 0) {
                        html = '<span class=\'text-amber-600 flex items-center gap-1.5\'><span class=\'w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse\'></span>Faltan ' + data.dias_restantes + ' ' + (data.dias_restantes === 1 ? 'día' : 'días') + '</span>';
                    } else if (data.dias_restantes === 0) {
                        html = '<span class=\'text-orange-600 flex items-center gap-1.5 animate-pulse\'><span class=\'w-1.5 h-1.5 rounded-full bg-orange-500\'></span>¡Vence hoy!</span>';
                    } else {
                        html = '<span class=\'text-rose-600 flex items-center gap-1.5\'><span class=\'w-1.5 h-1.5 rounded-full bg-rose-500\'></span>Fecha vencida (Venció hace ' + Math.abs(data.dias_restantes) + ' ' + (Math.abs(data.dias_restantes) === 1 ? 'día' : 'días') + ')</span>';
                    }
                    statusEl.innerHTML = html;
                }
                this.successMessage = `Se han añadido +${dias} días de plazo. Nueva fecha límite: ${data.nueva_fecha_larga}.`;
                this.showProrrogaModal = false;
                this.showSuccessModal = true;
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error al intentar prorrogar el plazo.');
        })
        .finally(() => { this.isProrrogando = false; });
    }
}" x-ref="detallesProyecto">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('gestor.dashboard') }}" class="p-2 bg-white border border-gray-200 rounded-xl text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 hover:border-indigo-100 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 leading-tight">Detalles del Proyecto</h1>
                <p class="text-sm text-gray-500 mt-1">Información detallada, evaluación y control del proyecto.</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            {{-- La acción de Desactivar se realiza desde la lista de trabajos (Panel Gestor) --}}
            <a href="{{ route('gestor.dashboard') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-gray-600 bg-white border border-gray-200 hover:text-[#07321e] hover:bg-gray-50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                Ir a la lista
            </a>
            <a href="{{ route('trabajo.archivo', $trabajo->id_trabajo) }}?download=1"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#07321e] text-white rounded-xl font-bold text-sm hover:bg-[#07321e]/80 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Descargar PDF
            </a>
        </div>
    </div>

    {{-- Mensajes de sesión --}}
    @if(session('warning'))
    <div class="mb-8 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
            <h4 class="text-xs font-bold text-amber-800 uppercase tracking-wide">Atención</h4>
            <p class="text-[11px] text-amber-700 mt-1 leading-relaxed">{{ session('warning') }}</p>
        </div>
    </div>
    @elseif(session('error'))
    <div class="mb-8 p-4 bg-rose-50 border border-rose-200 rounded-xl flex items-start gap-3">
        <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19H19a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0l-7 12A2 2 0 005.07 19z" />
        </svg>
        <div>
            <h4 class="text-xs font-bold text-rose-800 uppercase tracking-wide">Error</h4>
            <p class="text-[11px] text-rose-700 mt-1 leading-relaxed">{{ session('error') }}</p>
        </div>
    </div>
    @elseif(session('success'))
    <div class="mb-8 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-3">
        <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
            <h4 class="text-xs font-bold text-emerald-800 uppercase tracking-wide">Operación Exitosa</h4>
            <p class="text-[11px] text-emerald-700 mt-1 leading-relaxed">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    {{-- TIMELINE --}}
    @php
    $historial = $trabajo->historialEstados->sortBy('created_at');
    $revisionFinalizadaPorEvaluador = $algunoFinalizado;
    $estadoProceso = match(true) {
        $trabajo->estado === 'finalizado' => ['label' => 'Finalizado', 'class' => 'bg-teal-50 text-teal-700 border-teal-200'],
        ($trabajo->estado ?? null) === 'rechazada' => ['label' => 'Rechazada', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'],
        $puedeSubirInformeFinal => ['label' => 'Esperando Inf. Final', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
        !$todosEvalFinalizados && $trabajo->estado === 'version_corregida_subida' => ['label' => 'Corrección subida', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
        !$todosEvalFinalizados && $trabajo->estado === 'en_revision' => ['label' => 'En revisión', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
        $todosEvalFinalizados => ['label' => 'Calificada', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        $revisionFinalizadaPorEvaluador => ['label' => 'Esperando finalización', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
        $trabajo->estado === 'retroalimentacion_emitida' => ['label' => 'Retroalimentación emitida', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
        $trabajo->estado === 'aprobado' => ['label' => 'Aprobado', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        default => ['label' => 'Subido', 'class' => 'bg-gray-50 text-gray-700 border-gray-200'],
    };
    @endphp
    {{-- ACCIONES DE RE-EVALUACIÓN / NUEVA VERSIÓN (Destacadas en la parte superior) --}}
    @if(($todosEvalFinalizados && ($propuestaRechazada || $requiereNuevaVersion || $puedeSubirInformeFinal)) || (!$todosEvalFinalizados && $trabajo->estado === 'version_corregida_subida'))
    <div class="bg-white rounded-2xl shadow-sm border-2 {{ (!$todosEvalFinalizados && $trabajo->estado === 'version_corregida_subida') ? 'border-blue-300' : ($propuestaRechazada ? 'border-rose-300' : ($requiereNuevaVersion ? 'border-amber-300' : 'border-emerald-300')) }} overflow-hidden mb-8">
        @if(!$todosEvalFinalizados && $trabajo->estado === 'version_corregida_subida')
            <div class="p-6">
                <div class="flex items-center gap-4 mb-2">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 leading-tight">Documento corregido cargado correctamente</h3>
                        <p class="text-xs text-gray-500 mt-1">Esperando que los evaluadores revisen la nueva versión del documento.</p>
                    </div>
                </div>
            </div>
        @elseif($propuestaRechazada)
            <div class="p-6">
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl mb-4">
                    <h4 class="text-xs font-bold text-rose-800 uppercase tracking-wide mb-1">Propuesta Rechazada por Ambos</h4>
                    <p class="text-[11px] text-rose-700 leading-relaxed">Ambos evaluadores han rechazado la propuesta, los estudiantes deben decidir si continuar con el proyecto subiendo una nueva propuesta.</p>
                </div>
                <form action="{{ route('gestor.trabajo.decidirPropuesta', $trabajo->id_trabajo) }}" method="POST" enctype="multipart/form-data" class="space-y-4" x-data="{ decision: '' }">
                    @csrf
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="decision" value="si" x-model="decision" class="text-[#07321e] focus:ring-[#07321e] w-4 h-4 border-gray-300">
                            <span class="text-sm font-bold text-gray-700">Sí, subir nueva propuesta</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="decision" value="no" x-model="decision" class="text-rose-600 focus:ring-rose-600 w-4 h-4 border-gray-300">
                            <span class="text-sm font-bold text-gray-700">No, finalizar proceso</span>
                        </label>
                    </div>
                    <div x-show="decision === 'si'" x-transition class="space-y-3">
                        <label class="block text-xs font-bold text-gray-700">Archivo PDF de la nueva propuesta</label>
                        <input type="file" name="archivo_pdf" accept=".pdf" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-[#c2d500]/20 file:text-[#07321e] hover:file:bg-[#c2d500]/30 transition-all border border-gray-200 rounded-xl p-1 bg-white">
                    </div>
                    <div x-show="decision !== ''" x-transition>
                        <button type="submit" class="w-full py-2.5 bg-[#07321e] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#07321e]/90 transition-all">
                            Confirmar decisión
                        </button>
                    </div>
                </form>
            </div>
        @elseif($requiereNuevaVersion)
            <div class="p-6">
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl mb-4">
                    <h4 class="text-xs font-bold text-amber-800 uppercase tracking-wide mb-1">Requiere Nueva Versión</h4>
                    <p class="text-[11px] text-amber-700 leading-relaxed">
                        Se requiere subir un nuevo archivo corregido con base en las observaciones registradas por los evaluadores. Solo los evaluadores que solicitaron observaciones o correcciones deberán volver a revisar la nueva versión.
                    </p>
                </div>
                <form action="{{ route('gestor.trabajo.subirNuevaVersion', $trabajo->id_trabajo) }}" method="POST" enctype="multipart/form-data" 
                    class="space-y-4" x-data="{ isSubmitting: false, fileSelected: false, fileName: '' }" 
                    @submit="if(!fileSelected) { $event.preventDefault(); alert('Selecciona un archivo PDF'); return; } isSubmitting = true;">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-2">Archivo PDF Corregido</label>
                        <div class="relative border-2 border-dashed rounded-xl p-6 text-center transition-all cursor-pointer"
                            :class="fileSelected ? 'border-[#07321e] bg-[#07321e]/5' : 'border-gray-200 hover:border-[#07321e]/30'"
                            @click="$refs.fileInput.click()"
                            @dragover.prevent="$el.classList.add('border-[#07321e]', 'bg-[#07321e]/5')"
                            @dragleave.prevent="if (!fileSelected) $el.classList.remove('border-[#07321e]', 'bg-[#07321e]/5')"
                            @drop.prevent="$el.classList.remove('border-[#07321e]', 'bg-[#07321e]/5'); $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))">
                            <template x-if="!fileSelected">
                                <div>
                                    <svg class="w-10 h-10 mx-auto text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="text-sm text-gray-500 mb-1">
                                        <span class="font-bold text-[#07321e]">Haz clic</span> o arrastra el archivo aquí
                                    </p>
                                    <p class="text-xs text-gray-600">Solo PDF</p>
                                </div>
                            </template>
                            <template x-if="fileSelected">
                                <div>
                                    <div class="w-10 h-10 mx-auto bg-[#07321e] rounded-xl flex items-center justify-center mb-2">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-[#07321e] mb-1" x-text="fileName"></p>
                                    <p class="text-xs text-gray-600">Haz clic para cambiar el archivo</p>
                                </div>
                            </template>
                        </div>
                        <input type="file" name="archivo_pdf" accept=".pdf,application/pdf"
                            x-ref="fileInput" class="hidden" required
                            @change="fileSelected = $el.files.length > 0; fileName = $el.files[0]?.name || ''">
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-[#07321e] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#07321e]/90 transition-all flex justify-center items-center gap-2" :disabled="isSubmitting" :class="{'opacity-70 cursor-not-allowed': isSubmitting}">
                        <span x-show="!isSubmitting">Subir Nueva Versión</span>
                        <span x-show="isSubmitting" style="display: none;">Subiendo...</span>
                    </button>
                </form>
            </div>
        @elseif($puedeSubirInformeFinal)
            <div class="p-6">
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl mb-4">
                    <h4 class="text-xs font-bold text-emerald-800 uppercase tracking-wide mb-1">Aprobación Unánime</h4>
                    <p class="text-[11px] text-emerald-700 leading-relaxed">La propuesta ha sido aprobada por todos los evaluadores. Se debe esperar la entrega del informe final.</p>
                </div>
                <div class="text-center">
                    <a href="{{ route('gestor.trabajo.informe-final', $trabajo->id_trabajo) }}" class="inline-block w-full py-2.5 bg-[#07321e] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#07321e]/90 transition-all">
                        Subir Informe Final
                    </a>
                </div>
            </div>
        @endif
    </div>
    @endif

    {{-- GRID PRINCIPAL --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- COLUMNA IZQUIERDA --}}
        <div class="lg:col-span-2 space-y-8">

            {{-- INFORMACIÓN GENERAL (incluye el Acta) — recibe foco al llegar desde el flujo de asignación --}}
            <div id="card-acta" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden transition-all duration-700"
                :class="actaHighlight ? 'ring-4 ring-amber-300/70 border-amber-300 shadow-md' : ''">
                <div class="p-8">
                    <div class="flex items-start justify-between gap-4 mb-6">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-tight {{ match($tipo_nombre) { 'Investigación', 'Trabajo De Grado' => 'tag-trabajo', 'Emprendimiento' => 'tag-emprendimiento', 'Pasantía' => 'tag-pasantia', default => 'tag-default' } }}">
                                    {{ $tipo_nombre }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                    </svg>
                                    {{ $trabajo->codigo_proyecto ?? ('ID: ' . $trabajo->id_trabajo) }}
                                </span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 leading-tight">{{ $trabajo->titulo }}</h2>

                            {{-- Banners de acción --}}
                            @if($puedeSubirInformeFinal)
                            <div class="mt-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-3">
                                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <h4 class="text-xs font-bold text-emerald-800 uppercase tracking-wide">Propuesta Calificada</h4>
                                    <p class="text-[11px] text-emerald-700 mt-1 leading-relaxed">Todos los evaluadores han finalizado. Es necesario subir el documento final para convertirla en Trabajo de Grado.</p>
                                </div>
                            </div>
                            @elseif($trabajo->estado === 'retroalimentacion_emitida')
                            <div class="mt-3 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
                                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <h4 class="text-xs font-bold text-amber-800 uppercase tracking-wide">Retroalimentación Emitida</h4>
                                    <p class="text-[11px] text-amber-700 mt-1 leading-relaxed">Los jurados han finalizado sus observaciones. Esperando que los estudiantes suban la corrección.</p>
                                </div>
                            </div>
                            @elseif($trabajo->estado === 'finalizado')
                            <div class="mt-3 p-4 bg-teal-50 border border-teal-200 rounded-xl flex items-start gap-3">
                                <svg class="w-5 h-5 text-teal-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <h4 class="text-xs font-bold text-teal-800 uppercase tracking-wide">Proceso Finalizado</h4>
                                    <p class="text-[11px] text-teal-700 mt-1 leading-relaxed">El acta de sustentación fue registrada. El proceso del proyecto ha finalizado.</p>
                                </div>
                            </div>
                            @endif

                            {{-- Badges de estado --}}
                            <div class="flex items-center gap-2 flex-wrap mt-1">
                                @php
                                $estadoLabel = match(true) {
                                    $trabajo->estado === 'finalizado' => 'Finalizado',
                                    $trabajo->estado === 'rechazada' => 'Rechazada',
                                    ($todosEvalFinalizados && ($requiereNuevaVersion || $propuestaRechazada)) => 'En revisión',
                                    $todosEvalFinalizados => 'Calificada',
                                    $trabajo->estado === 'aprobado' => 'Aprobado',
                                    default => 'Sin calificar',
                                };
                                $estadoColor = match(true) {
                                    $trabajo->estado === 'finalizado' => 'bg-teal-50 text-teal-700 border-teal-200',
                                    $trabajo->estado === 'rechazada' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    ($todosEvalFinalizados && ($requiereNuevaVersion || $propuestaRechazada)) => 'bg-blue-50 text-blue-700 border-blue-200',
                                    $todosEvalFinalizados => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    $trabajo->estado === 'aprobado' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    default => 'bg-gray-50 text-gray-700 border-gray-200',
                                };
                                @endphp
                                <span class="px-2 py-0.5 rounded-lg border text-[10px] font-bold uppercase tracking-wider {{ $estadoColor }}">
                                    {{ $estadoLabel }}
                                </span>
                                @if($trabajo->retirado)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg border text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border-rose-200">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Desactivado
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg border text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-600 border-emerald-200">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Activo
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6 pt-6 border-t border-gray-100">
                        <div>
                            <span class="block text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-1">Fecha de Registro</span>
                            <span class="text-sm font-bold text-gray-700">{{ \Carbon\Carbon::parse($trabajo->fecha_subida)->format('d \d\e F, Y') }}</span>
                            @php $dias = (int) \Carbon\Carbon::parse($trabajo->fecha_subida)->diffInDays(); @endphp
                            <div class="flex items-center gap-1.5 mt-1 text-emerald-600">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-[10px] font-bold uppercase tracking-tight">Hace {{ $dias }} {{ $dias == 1 ? 'día' : 'días' }}</span>
                            </div>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-1">Nombre del Proyecto</span>
                            <div class="flex items-center gap-2">
                                <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                                <span class="text-sm font-bold text-gray-700 truncate">{{ basename($trabajo->archivo_pdf) }}</span>
                            </div>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-1">Nombre del Acta</span>
                            @if($trabajo->tieneActa())
                                <div class="flex items-center gap-2">
                                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                                    <span class="text-sm font-bold text-gray-700 truncate">{{ basename($trabajo->archivo_acta) }}</span>
                                </div>
                            @else
                                <div class="flex items-center gap-2">
                                    <div class="w-2.5 h-2.5 rounded-full bg-rose-500"></div>
                                    <span class="text-xs font-bold text-rose-600">Pendiente por subir</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- ACTA DE INICIO (Visualización) --}}
                    <div class="mt-6 pt-4 border-t border-dashed border-gray-200">
                        @if($trabajo->tieneActa())
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Acta de Inicio</label>
                                <p class="text-xs text-gray-600">Documento de acta asociado a este proyecto.</p>
                            </div>
                            <a href="{{ asset($trabajo->archivo_acta) }}" target="_blank" download
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#07321e] text-white rounded-xl font-bold hover:bg-[#03180d] transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Descargar Acta
                            </a>
                        </div>
                        @else
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700">Acta de Inicio</label>
                                <p class="text-xs text-amber-600 font-semibold mt-0.5">El Administrador aún no ha subido el acta de inicio.</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- SECCIÓN ACTA DE SUSTENTACIÓN — solo cuando ambos evaluadores calificaron 'PUEDE SUSTENTAR' o si ya fue subida --}}
            @if($mostrarActaSustentacion)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-8 py-5 border-b border-gray-100 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl {{ $trabajo->tieneActaSustentacion() ? 'bg-teal-100 text-teal-700' : 'bg-amber-50 text-amber-600' }} flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Acta de Sustentación</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Documento oficial de la sustentación. Al adjuntarlo, el proceso del proyecto <strong class="text-teal-700">finaliza</strong>.</p>
                        </div>
                    </div>
                    @if($trabajo->tieneActaSustentacion())
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-[10px] font-bold uppercase tracking-wider bg-teal-50 text-teal-700 border-teal-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Proceso Finalizado
                    </span>
                    @endif
                </div>

                <div class="p-8">
                    @if($trabajo->tieneActaSustentacion())
                    {{-- Acta ya subida: ver / descargar --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-lg bg-teal-50 border border-teal-100 flex items-center justify-center shrink-0 text-teal-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="min-w-0">
                                <span class="block text-xs font-bold text-gray-600 uppercase tracking-widest mb-1">Acta registrada</span>
                                <span class="text-sm font-bold text-gray-800 truncate block">{{ basename($trabajo->archivo_acta_sustentacion) }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ asset($trabajo->archivo_acta_sustentacion) }}" target="_blank" download
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#07321e] text-white rounded-xl font-bold hover:bg-[#03180d] transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                Descargar Acta
                            </a>
                        </div>
                    </div>
                    @else
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700">Acta de Sustentación</label>
                            <p class="text-xs text-amber-600 font-semibold mt-0.5">El Administrador aún no ha subido el acta de sustentación.</p>
                        </div>
                    </div>
                    @endif

                    @if($trabajo->tieneActaSustentacion())
                    <div class="mt-4 flex items-start gap-3 p-4 bg-teal-50 border border-teal-200 rounded-xl">
                        <svg class="w-5 h-5 text-teal-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h4 class="text-xs font-bold text-teal-800 uppercase tracking-wide">Proceso Finalizado</h4>
                            <p class="text-[11px] text-teal-700 mt-0.5 leading-relaxed">El acta de sustentación fue registrada. El proceso de este proyecto ha concluido; para reemplazar el documento usa la opción "Reemplazar".</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- ESTUDIANTES --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden" x-data="{ openEstudiantes: false }">
                <div class="px-8 py-5 border-b border-gray-100 flex items-center justify-between cursor-pointer select-none hover:bg-gray-50/50 transition-colors"
                    @click="openEstudiantes = !openEstudiantes">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-bold text-gray-800">Estudiantes</h3>
                        <span class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold" x-text="estudianteCount"></span>
                    </div>
                    <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-all" @click.stop="openEstudiantes = !openEstudiantes">
                        <span x-text="openEstudiantes ? 'Ocultar' : 'Mostrar'"></span>
                        <svg class="w-4 h-4 transform transition-transform duration-200" :class="openEstudiantes ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
                <div x-show="openEstudiantes" x-collapse x-cloak class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-8 py-4 text-[11px] font-bold text-gray-600 uppercase tracking-wider">Nombre Completo</th>
                                <th class="px-8 py-4 text-[11px] font-bold text-gray-600 uppercase tracking-wider">Correo</th>
                                <th class="px-8 py-4 text-[11px] font-bold text-gray-600 uppercase tracking-wider">Área</th>
                                <th class="px-8 py-4 text-[11px] font-bold text-gray-600 uppercase tracking-wider text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($trabajo->estudiante as $est)
                            <tr class="group hover:bg-gray-50/50 transition-colors" id="student-row-{{ $est->id_estudiante }}">
                                <td class="px-8 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-green font-bold text-xs group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-colors">
                                            {{ substr($est->nombre, 0, 1) }}{{ substr($est->apellido, 0, 1) }}
                                        </div>
                                        <span class="text-sm font-bold text-gray-700">{{ $est->nombre }} {{ $est->apellido }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-4 whitespace-nowrap text-sm text-gray-500">{{ $est->correo ?? '—' }}</td>
                                <td class="px-8 py-4 whitespace-nowrap text-sm text-gray-500">{{ optional($est->area)->nombre_area ?? '—' }}</td>
                                <td class="px-8 py-4 whitespace-nowrap text-right">
                                    <button @click="if(estudianteCount > 1) { studentToDelete = { id: '{{ $est->id_estudiante }}', name: '{{ $est->nombre }} {{ $est->apellido }}' }; motivoEliminacion = ''; showDeleteModal = true; } else { showRestrictionModal = true; }"
                                        class="p-2 text-gray-600 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all"
                                        :class="estudianteCount <= 1 ? 'opacity-20 grayscale' : ''">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-8 py-10 text-center text-sm text-gray-600 italic">No hay estudiantes registrados en este proyecto.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- DIRECTORES --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-8 py-5 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800">Director y Subdirector</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-8 py-4 text-[11px] font-bold text-gray-600 uppercase tracking-wider">Rol</th>
                                <th class="px-8 py-4 text-[11px] font-bold text-gray-600 uppercase tracking-wider">Nombre Completo</th>
                                <th class="px-8 py-4 text-[11px] font-bold text-gray-600 uppercase tracking-wider">Correo Electrónico</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($trabajo->directores as $dir)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-8 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase {{ $dir->pivot->rol === 'director' ? 'bg-[#c2d500]/20 text-[#07321e]' : 'bg-gray-100 text-gray-700' }}">
                                        {{ ucfirst($dir->pivot->rol ?? 'Director') }}
                                    </span>
                                </td>
                                <td class="px-8 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold text-gray-700">{{ $dir->nombre }} {{ $dir->apellido }}</span>
                                </td>
                                <td class="px-8 py-4 whitespace-nowrap text-sm text-gray-500">{{ $dir->correo_electronico }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-8 py-10 text-center text-sm text-gray-600 italic">No hay director o subdirector asignado a este proyecto.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- COLUMNA DERECHA --}}
        <div class="space-y-8">

            {{-- EVALUADORES --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-800">Cuerpo Evaluador</h3>
                    @if(!$trabajo->retirado)
                        @if(!$trabajo->tieneActa())
                        <span title="Adjunta el Acta del proyecto para habilitar la asignación"
                            class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1.5 rounded-lg flex items-center gap-1.5 cursor-not-allowed">
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Requiere Acta
                        </span>
                        @endif
                    @endif
                </div>
                <div class="p-6 space-y-4">
                    @forelse($trabajo->evaluadores as $eval)
                    @php
                    $finalizo = $eval->pivot->estado_revision === 'Finalizado' && empty($eval->pivot->requiere_nueva_revision);
                    $decisionEval = $eval->pivot->decision_evaluador ?? null;
                    $rechazado = $decisionEval === 'rechazado';
                    $fechaLimite = $eval->pivot->fecha_limite_revision ? \Carbon\Carbon::parse($eval->pivot->fecha_limite_revision) : null;
                    $hoy = \Carbon\Carbon::now()->startOfDay();
                    $diasRestantes = $fechaLimite ? (int) $hoy->diffInDays($fechaLimite, false) : null;
                    // Fecha en la que el evaluador realizó la evaluación (por historial, con respaldo en la evaluación compartida)
                    $fechaEvaluacion = null;
                    if ($finalizo) {
                        $historialEval = $trabajo->historialEstados
                            ->whereIn('estado', ['evaluado', 'evaluacion_completada'])
                            ->where('user_id', $eval->usuario->id_usuario ?? null)
                            ->sortByDesc('created_at')
                            ->first();
                        $fechaEvaluacion = $historialEval ? \Carbon\Carbon::parse($historialEval->created_at) : null;
                        if (!$fechaEvaluacion) {
                            $evalTrabajo = $trabajo->evaluaciones->first();
                            if ($evalTrabajo && $evalTrabajo->updated_at) {
                                $fechaEvaluacion = \Carbon\Carbon::parse($evalTrabajo->updated_at);
                            }
                        }
                    }
                    @endphp
                    <div class="flex flex-col gap-3 p-4 bg-gray-50 rounded-2xl border {{ $rechazado ? 'border-red-200 bg-red-50/50' : ($finalizo ? 'border-emerald-200' : 'border-gray-100') }}">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl {{ $rechazado ? 'bg-red-100 text-red-700' : ($finalizo ? 'bg-emerald-100 text-emerald-700' : 'bg-[#c2d500] text-[#07321e]') }} flex items-center justify-center font-bold shrink-0">
                                {{ substr($eval->usuario->nombre, 0, 1) }}{{ substr($eval->usuario->apellido, 0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0 text-left">
                                <p class="text-sm font-bold text-gray-900 truncate">{{ $eval->usuario->nombre }} {{ $eval->usuario->apellido }}</p>
                                @if($rechazado)
                                    <p class="text-[10px] text-red-600 font-bold uppercase tracking-widest">Rechazado</p>
                                @else
                                    <p class="text-[10px] {{ $finalizo ? 'text-emerald-600' : 'text-amber-600' }} font-bold uppercase tracking-widest">
                                        {{ $finalizo ? 'Finalizado' : 'Pendiente' }}
                                    </p>
                                @endif
                            </div>
                            @if($rechazado)
                            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            @elseif($finalizo)
                            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            @else
                            <svg class="w-5 h-5 text-amber-400 shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            @endif
                        </div>

                        @if($rechazado && ($eval->pivot->motivo_rechazo ?? null))
                        <div class="pt-2 border-t border-red-200/60 text-left">
                            <p class="text-[10px] font-bold text-red-500 uppercase tracking-wider mb-1">Motivo del rechazo</p>
                            <p class="text-xs text-red-700">{{ $eval->pivot->motivo_rechazo }}</p>
                        </div>
                        @endif

                        @if(!$rechazado && $finalizo && $fechaEvaluacion)
                        <div class="pt-2 border-t border-emerald-200/60 flex items-center justify-between text-left">
                            <span class="text-[11px] text-gray-500 font-semibold">Fecha de Evaluación:</span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $fechaEvaluacion->format('d/m/Y') }}
                            </span>
                        </div>
                        @elseif(!$rechazado && $fechaLimite)
                        <div class="pt-2 border-t border-gray-200/60 flex flex-col gap-1.5 text-left">
                            <div class="flex items-center justify-between text-[11px] text-gray-500 font-semibold">
                                <span>Fecha Límite:</span>
                                <span id="deadline-date-{{ $eval->id_profesor }}">{{ $fechaLimite->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex items-center justify-between mt-0.5">
                                <span class="text-[11px] font-bold" id="deadline-status-container-{{ $eval->id_profesor }}">
                                    @if($diasRestantes > 0)
                                    <span class="text-amber-600 flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Faltan {{ $diasRestantes }} {{ $diasRestantes == 1 ? 'día' : 'días' }}
                                    </span>
                                    @elseif($diasRestantes === 0)
                                    <span class="text-orange-600 flex items-center gap-1.5 animate-pulse">
                                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                                        ¡Vence hoy!
                                    </span>
                                    @else
                                    <span class="text-rose-600 flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Venció hace {{ abs($diasRestantes) }} {{ abs($diasRestantes) == 1 ? 'día' : 'días' }}
                                    </span>
                                    @endif
                                </span>
                                @if($diasRestantes !== null && $diasRestantes < 0)
                                <button type="button"
                                    @click="evaluadorProrroga = { id: '{{ $eval->id_profesor }}', name: '{{ $eval->usuario->nombre }} {{ $eval->usuario->apellido }}' }; prorrogaDias = 21; showProrrogaModal = true;"
                                    class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-lg transition-colors">
                                    Prorrogar
                                </button>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="text-center py-6">
                        <div class="w-12 h-12 bg-rose-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <p class="text-xs text-gray-600 font-medium">Sin evaluadores asignados</p>
                        @if(!$trabajo->tieneActa())
                        <p class="mt-1 text-[11px] text-gray-500 italic">Adjunta el Acta del proyecto para habilitar la asignación.</p>
                        @endif
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- RESULTADO DE EVALUACIÓN / ESTADO --}}
            @if($todosEvalFinalizados)
                {{-- TARJETA: EVALUACIÓN COMPLETADA --}}
                @php
                $primeraEval = $trabajo->evaluaciones->first();
                $headerResultadoClases = match($primeraEval->resultado ?? '') {
                    'puede_sustentar', 'aceptada' => 'bg-emerald-50 text-emerald-700 border-emerald-300',
                    'sustentacion_con_correcciones', 'aceptada_con_mejoras' => 'bg-amber-50 text-amber-700 border-amber-300',
                    'no_sustentar', 'rechazada' => 'bg-rose-50 text-rose-700 border-rose-300',
                    default => 'bg-gray-50 text-gray-700 border-gray-200'
                };
                $evalResultadoTexto = fn($r) => match($r) {
                    'puede_sustentar' => 'Puede Sustentar', 'no_sustentar' => 'No Sustentar',
                    'sustentacion_con_correcciones' => 'Sustentación con Correcciones',
                    'aceptada' => 'Aceptada', 'aceptada_con_mejoras' => 'Aceptada con Mejoras',
                    'rechazada' => 'Rechazada',
                    default => $r ? ucfirst(str_replace('_', ' ', $r)) : 'Sin resultado'
                };
                $evalColorBadge = fn($r) => match($r) {
                    'puede_sustentar', 'aceptada' => 'bg-emerald-100 text-emerald-800',
                    'sustentacion_con_correcciones', 'aceptada_con_mejoras' => 'bg-amber-100 text-amber-800',
                    'no_sustentar', 'rechazada' => 'bg-rose-100 text-rose-800',
                    default => 'bg-gray-100 text-gray-800'
                };
                @endphp
                <div class="bg-white rounded-2xl shadow-sm border-2 {{ $headerResultadoClases }} overflow-hidden">
                    <div class="px-6 py-4 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center shrink-0 text-emerald-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-bold text-gray-900">Evaluación Completada</h3>
                            <p class="text-xs text-gray-700 mt-0.5">Todos los evaluadores han finalizado la revisión.</p>
                        </div>
                    </div>
                    <div class="p-6 space-y-3">
                        @foreach($trabajo->evaluadores as $evaluador)
                        @php
                        $evalObj = $trabajo->evaluaciones->where('id_profesor', $evaluador->id_profesor)->first();
                        $evalNombre = optional($evaluador->usuario)->nombre ?? ($evaluador->nombre ?? 'Evaluador');
                        $evalApellido = optional($evaluador->usuario)->apellido ?? ($evaluador->apellido ?? '');
                        $evalNota = $evalObj->nota_final ?? null;
                        $evalResultado = $evalObj->resultado ?? null;
                        @endphp
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-[#c2d500] flex items-center justify-center text-[#07321e] font-bold text-xs">
                                        {{ substr($evalNombre, 0, 1) }}{{ substr($evalApellido, 0, 1) }}
                                    </div>
                                    <span class="text-sm font-bold text-gray-700">{{ $evalNombre }} {{ $evalApellido }}</span>
                                </div>
                                @if($evalNota !== null)
                                <span class="text-lg font-bold text-gray-900">{{ number_format((float) $evalNota, 2) }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-tight {{ $evalColorBadge($evalResultado) }}">
                                    {{ $evalResultadoTexto($evalResultado) }}
                                </span>
                            </div>
                            @if(!empty($evalObj->observaciones_globales))
                            <div class="mt-2 pt-2 border-t border-gray-200/60">
                                <p class="text-[11px] text-gray-600 italic">{{ $evalObj->observaciones_globales }}</p>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    

                </div>

            @elseif($algunoFinalizado)
                {{-- TARJETA: ESPERANDO FINALIZACIÓN --}}
                @php
                $finalizadosCount = $trabajo->evaluadores->filter(fn($e) => $e->pivot->estado_revision === 'Finalizado' && empty($e->pivot->requiere_nueva_revision))->count();
                $totalEvaluadores = $trabajo->evaluadores->count();
                @endphp
                <div class="bg-white rounded-2xl shadow-sm border-2 border-amber-200 overflow-hidden">
                    <div class="px-6 py-4 bg-amber-50 border-b border-amber-200 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-amber-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-bold text-gray-900">Esperando finalización</h3>
                            <p class="text-xs text-gray-700 mt-0.5">{{ $finalizadosCount }} de {{ $totalEvaluadores }} evaluadores han finalizado.</p>
                        </div>
                        <span class="px-2 py-1 bg-amber-200 text-amber-800 rounded-lg text-xs font-bold uppercase">{{ $finalizadosCount }}/{{ $totalEvaluadores }}</span>
                    </div>
                    <div class="p-6 space-y-3">
                        @foreach($trabajo->evaluadores as $eval)
                        @php
                        $finalizo = $eval->pivot->estado_revision === 'Finalizado' && empty($eval->pivot->requiere_nueva_revision);
                        @endphp
                        <div class="flex items-center gap-3 p-3 rounded-xl {{ $finalizo ? 'bg-emerald-50 border border-emerald-200' : 'bg-gray-50 border border-gray-200' }}">
                            <div class="w-8 h-8 rounded-full {{ $finalizo ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-500' }} flex items-center justify-center text-xs font-bold shrink-0">
                                {{ substr($eval->usuario->nombre, 0, 1) }}{{ substr($eval->usuario->apellido, 0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-700 truncate">{{ $eval->usuario->nombre }} {{ $eval->usuario->apellido }}</p>
                                <p class="text-[11px] {{ $finalizo ? 'text-emerald-600' : 'text-gray-600' }} font-medium">
                                    {{ $finalizo ? 'Finalizado — Firmando evaluación' : 'Pendiente por finalizar' }}
                                </p>
                            </div>
                            @if($finalizo)
                            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            @else
                            <svg class="w-5 h-5 text-amber-400 shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            @endif
                        </div>
                        @endforeach
                        <div class="pt-3 border-t border-gray-100">
                            <p class="text-[11px] text-gray-600 text-center italic">La calificación se mostrará una vez que ambos evaluadores finalicen y firmen.</p>
                        </div>
                    </div>
                </div>

            @else
                {{-- PANEL INFORMATIVO --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-800">Evaluaciones</h3>
                        <p class="text-xs text-gray-600 mt-1">Disponible cuando ambos evaluadores finalicen.</p>
                    </div>
                    <div class="p-6">
                        @php
                        $etapas = [
                            ['key' => 'subido', 'label' => 'Documento subido'],
                            ['key' => 'en_revision', 'label' => 'En revisión'],
                            ['key' => 'retroalimentacion_emitida', 'label' => 'Retroalimentación emitida'],
                            ['key' => 'version_corregida_subida', 'label' => 'Corrección subida'],
                            ['key' => 'aprobado', 'label' => 'Aprobado'],
                        ];
                        $estadoActual = $trabajo->estado ?? 'subido';
                        $estadoIndex = array_search($estadoActual, array_column($etapas, 'key'));
                        if ($estadoIndex === false) $estadoIndex = 0;
                        @endphp
                        <div class="space-y-3">
                            @foreach($etapas as $i => $etapa)
                            @php
                            $esPasado = $i < $estadoIndex;
                            $esActual = $i === $estadoIndex;
                            $esFuturo = $i > $estadoIndex;
                            @endphp
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 {{ $esPasado ? 'bg-emerald-100 text-emerald-600' : ($esActual ? 'bg-[#c2d500] text-[#07321e]' : 'bg-gray-100 text-gray-500') }}">
                                    @if($esPasado)
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                    @elseif($esActual)
                                    <div class="w-2 h-2 rounded-full bg-[#07321e]"></div>
                                    @else
                                    <div class="w-2 h-2 rounded-full bg-gray-300"></div>
                                    @endif
                                </div>
                                <span class="text-xs font-{{ $esActual ? 'bold' : 'medium' }} {{ $esPasado ? 'text-emerald-600 line-through' : ($esActual ? 'text-gray-900' : 'text-gray-600') }}">
                                    {{ $etapa['label'] }}
                                </span>
                                @if($esActual)
                                <span class="ml-auto text-[9px] font-bold bg-[#c2d500]/20 text-[#07321e] px-1.5 py-0.5 rounded uppercase tracking-wide">Actual</span>
                                @endif
                            </div>
                            @if(!$loop->last)
                            <div class="ml-3 w-px h-3 {{ $esPasado ? 'bg-emerald-200' : 'bg-gray-100' }}"></div>
                            @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL: Eliminar Estudiante --}}
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showDeleteModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                <div class="p-8 text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-rose-50 mb-6">
                        <svg class="h-8 w-8 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">¿Eliminar Estudiante?</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Estás a punto de desvincular a <span class="font-bold text-gray-900" x-text="studentToDelete.name"></span> de este proyecto.
                    </p>
                    <div class="mt-4 text-left mb-8">
                        <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-2 ml-1">Motivo de la eliminación</label>
                        <textarea x-model="motivoEliminacion" rows="3"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl text-sm focus:ring-2 focus:ring-rose-500 focus:bg-white transition-all outline-none resize-none font-medium text-gray-700 shadow-inner"
                            placeholder="Ej: El estudiante se retiró de la asignatura..."></textarea>
                    </div>
                    <div class="flex flex-col sm:flex-row-reverse gap-3">
                        <button @click="deleteStudent(studentToDelete.id)" :disabled="isDeleting"
                            class="w-full px-8 py-3 rounded-2xl bg-rose-600 text-white font-bold hover:bg-rose-700 transition duration-300 shadow-lg shadow-rose-200 flex items-center justify-center gap-2">
                            <span x-show="!isDeleting">Sí, Eliminar</span>
                            <svg x-show="isDeleting" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                        <button type="button" @click="showDeleteModal = false" class="w-full px-8 py-3 rounded-2xl bg-white text-gray-500 font-bold border border-gray-200 hover:text-gray-700 hover:bg-gray-100 transition-all">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Restricción --}}
    <div x-show="showRestrictionModal" x-cloak class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showRestrictionModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                <div class="p-8 text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-amber-50 mb-6">
                        <svg class="h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Acción Restringida</h3>
                    <p class="text-sm text-gray-500 mb-8 font-medium">No se puede eliminar al único estudiante del proyecto.</p>
                    <button type="button" @click="showRestrictionModal = false" class="w-full px-8 py-3 rounded-2xl bg-gray-900 text-white font-bold hover:bg-black transition duration-300 shadow-lg">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Prórroga --}}
    <div x-show="showProrrogaModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showProrrogaModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                <div class="p-8 text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-indigo-50 mb-6">
                        <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Prorrogar Plazo</h3>
                    <p class="text-sm text-gray-500 mb-6 font-medium">
                        Añadir tiempo adicional para <span class="font-bold text-gray-900" x-text="evaluadorProrroga.name"></span>.
                    </p>
                    <div class="mt-4 text-left mb-8">
                        <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-3 ml-1 text-center">Extensión de plazo</label>
                        <div class="flex items-center justify-center gap-2 px-4 py-3 bg-indigo-50 border border-indigo-100 rounded-xl">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-bold text-indigo-700">+21 Días adicionales</span>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row-reverse gap-3">
                        <button @click="prorrogarPlazo(evaluadorProrroga.id, prorrogaDias)" :disabled="isProrrogando"
                            class="w-full px-8 py-3 rounded-2xl bg-[#07321e] text-white font-bold hover:bg-black transition duration-300 shadow-lg flex items-center justify-center gap-2">
                            <span x-show="!isProrrogando">Confirmar Prórroga</span>
                            <svg x-show="isProrrogando" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                        <button type="button" @click="showProrrogaModal = false" class="w-full px-8 py-3 rounded-2xl bg-white text-gray-500 font-bold border border-gray-200 hover:text-gray-700 hover:bg-gray-100 transition-all">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Éxito --}}
    <div x-show="showSuccessModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showSuccessModal = false"></div>
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-8 text-center z-10 border border-gray-100">
            <div class="w-16 h-16 rounded-full bg-green-50 text-green-600 flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">¡Prórroga Exitosa!</h2>
            <p class="text-gray-700 text-sm leading-relaxed mb-8 font-medium" x-text="successMessage"></p>
            <button @click="showSuccessModal = false" class="w-full py-3 rounded-xl bg-gray-900 text-white font-bold hover:bg-black transition-colors">Aceptar</button>
        </div>
    </div>
</div>
@endsection

