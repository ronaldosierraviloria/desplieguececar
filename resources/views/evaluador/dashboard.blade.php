@extends('layouts.baseEvaluador')

@section('title', 'Panel del Evaluador - Evaluación')


@section('content')
<div x-data="evaluadorFlow()">

    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-semibold text-gray-800">Evaluación de Proyectos</h1>
    </div>

    {{-- DASHBOARD CARDS --}}
    <section x-show="vistaActual === 'dashboard'" class="transition-all duration-300">
        <div class="grid grid-cols-1 gap-6">
            @forelse ($trabajosAsignados as $trabajo)
            @php
            $tipo_nombre = $trabajo->tipo->nombre_tipo ?? 'Sin tipo';
            $tagClass = match($tipo_nombre) {
            'Investigación', 'Trabajo De Grado' => 'tag-trabajo',
            'Emprendimiento' => 'tag-emprendimiento',
            'Pasantía' => 'tag-pasantia',
            default => 'tag-default'
            };
            $tieneProgreso = $trabajo->evaluaciones->isNotEmpty();
            @endphp

            <div class="project-card bg-white rounded-2xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-200 group overflow-hidden"
                id="trabajo-card-{{ $trabajo->id_trabajo }}">

                {{-- Cabecera --}}
                <div class="px-4 pt-4 pb-3 border-b border-gray-100">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide shrink-0 {{ $tagClass }}">
                            {{ $tipo_nombre }}
                        </span>
                    </div>
                </div>

                {{-- Cuerpo --}}
                <div class="px-4 py-4 space-y-3">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-500">Título de la propuesta</p>
                        <h3 class="text-base font-bold text-gray-900 leading-snug group-hover:text-[#07321e] transition-colors mt-1" title="{{ $trabajo->titulo }}">
                            {{ $trabajo->titulo }}
                        </h3>
                    </div>

                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-500">Código del proyecto</p>
                        <span class="inline-flex items-center mt-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-[#07321e]/5 text-[#07321e] border border-[#07321e]/15">
                            {{ $trabajo->codigo_proyecto ?? ('ID: ' . $trabajo->id_trabajo) }}
                        </span>
                    </div>

                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-500">Estudiantes</p>
                        <div class="flex flex-wrap gap-1.5 mt-1.5">
                            @forelse($trabajo->estudiante as $est)
                            <div class="inline-flex items-center justify-between gap-2 bg-gray-50 border border-gray-100 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-gray-800 min-w-0">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-[9px] font-bold text-gray-600 shrink-0">{{ substr($est->nombre, 0, 1) }}{{ substr($est->apellido, 0, 1) }}</span>
                                    <span class="truncate">{{ $est->nombre }} {{ $est->apellido }} - </span>
                                </div>
                                @if(!empty($est->area) && !empty($est->area->nombre_area))
                                <span class="text-[15px] font-medium text-gray-800 whitespace-nowrap">{{ $est->area->nombre_area }}</span>
                                @endif
                            </div>
                            @empty
                            <span class="text-sm text-gray-600 italic">Sin estudiantes</span>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-500">Directores</p>
                        <div class="mt-1.5 space-y-1.5">
                            @forelse($trabajo->directores as $director)
                            <div class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-sm font-medium text-gray-700 bg-indigo-50 border border-indigo-100">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5z"/></svg>
                                {{ $director->nombre }} {{ $director->apellido }}
                            </div>
                            @empty
                            <p class="text-sm text-gray-600 italic">Sin directores asignados</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Pie / Acciones compacto --}}
                @php
                    $miEvaluacion = $trabajo->evaluadores->where('id_profesor', $usuario->profesor->id_profesor)->first();
                    $requiereNueva = $miEvaluacion && !empty($miEvaluacion->pivot->requiere_nueva_revision);
                    $miRevisionFinalizada = ($miEvaluacion && $miEvaluacion->pivot->estado_revision === 'Finalizado' && !$requiereNueva);

                    $otroEvaluador = $trabajo->evaluadores->where('id_profesor', '!=', $usuario->profesor->id_profesor)->first();
                    $otroRequiereNueva = $otroEvaluador && !empty($otroEvaluador->pivot->requiere_nueva_revision);
                    $otroRevisionFinalizada = ($otroEvaluador && $otroEvaluador->pivot->estado_revision === 'Finalizado' && !$otroRequiereNueva);

                    $revisionFinalizada = $miRevisionFinalizada && $otroRevisionFinalizada;
                    $esperandoOtro = $miRevisionFinalizada && !$otroRevisionFinalizada;
                    $evaluacionDisponible = $revisionFinalizada;

                    $badgeRetro = null;
                    $tiempoRestante = null;
                    $tiempoClase = 'text-gray-600';

                    $fechaLimite = \Carbon\Carbon::parse($trabajo->pivot->fecha_limite_revision);
                    $hoy = \Carbon\Carbon::now();
                    $diasRestantes = (int) $hoy->diffInDays($fechaLimite, false);

                    if ($miRevisionFinalizada) {
                        $badgeRetro = ['text' => 'Revisión finalizada', 'color' => 'bg-gray-100 text-gray-600 border-gray-200'];
                        $tiempoRestante = 'Revisión cerrada';
                        $tiempoClase = 'text-gray-500';
                    } else {
                        $tiempoRestante = $diasRestantes < 0 ? 'Vencido' : "{$diasRestantes} día(s)";
                        $tiempoClase = $diasRestantes <= 3 ? 'text-red-600 font-bold' : 'text-[#07321e]';
                    }
                @endphp


                <div class="px-4 py-3 border-t border-gray-100 bg-gray-50/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex flex-col gap-2 min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-500">Estado y plazo</p>
                        <div class="flex items-center gap-2 flex-wrap">
                        @php $miEval = $trabajo->evaluaciones->first(); @endphp
                        @if($revisionFinalizada && $trabajo->plantilla_rubrica === 'propuesta_de_grado')
                            @php
                                $esAceptada = $miEval && strtolower(trim($miEval->resultado)) === 'aceptada';
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $esAceptada ? 'Esperando documento final' : 'Esperando Doc. Corregido' }}
                            </span>
                        @elseif($revisionFinalizada && $miEval && $miEval->nota_final !== null)
                            @php
                                $resTexto = match($miEval->resultado ?? '') {
                                    'aceptada', 'puede_sustentar' => 'Aprobado',
                                    'aceptada_con_mejoras', 'sustentacion_con_correcciones' => 'Con observaciones',
                                    'rechazada', 'no_sustentar' => 'Rechazado',
                                    default => 'Finalizado'
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">{{ number_format($miEval->nota_final, 1) }}</span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold {{ $resTexto === 'Aprobado' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : ($resTexto === 'Rechazado' ? 'bg-red-50 text-red-700 border-red-100' : 'bg-amber-50 text-amber-700 border-amber-100') }}">{{ $resTexto }}</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-sm font-bold {{ $tiempoClase }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                @if(str_contains($tiempoRestante, 'día'))
                                    Faltan {{ $tiempoRestante }}
                                @else
                                    {{ $tiempoRestante }}
                                @endif
                            </span>
                        @endif
                        @if(!$miRevisionFinalizada)
                        <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-700"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><span class="font-bold">Fecha límite:</span> {{ $fechaLimite->format('d/m/Y') }}</span>
                        @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 flex-wrap">
                        <a href="{{ route('trabajo.archivo', $trabajo->id_trabajo) }}" target="_blank"
                            class="inline-flex items-center gap-1 px-2 py-1.5 text-[10px] font-bold text-white bg-[#07321e] rounded-md hover:bg-[#1a4d2e] transition-all"
                            title="Descargar PDF">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Descargar PDF
                            </a>
                        
                            @php
                                $aceptoTerminos = ($trabajo->pivot->decision_evaluador === 'aceptado') && $trabajo->pivot->terminos_aceptados && $trabajo->pivot->datos_aceptados;
                            @endphp
                            <button @click="iniciarEvaluacion({{ $trabajo->id_trabajo }}, '{{ addslashes($trabajo->titulo) }}', '{{ asset($trabajo->archivo_pdf ?? '') }}', {{ $aceptoTerminos ? 'true' : 'false' }})"
                                class="inline-flex items-center gap-1 px-2 py-1.5 text-[10px] font-bold text-[#07321e] bg-[#c2d500]/15 border border-[#c2d500]/30 rounded-md hover:bg-[#c2d500]/30 transition-all">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $tieneProgreso ? 'Continuar Evaluación' : 'Evaluar Propuesta' }}
                            </button>
                        
                    </div>
                </div>
            </div>
            @empty
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 px-6 py-16 text-center flex flex-col items-center">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">No tienes trabajos asignados</h3>
                <p class="text-sm text-gray-500 mt-1 max-w-sm">Los proyectos que te sean asignados aparecerán aquí para ser evaluados.</p>
            </div>
            @endforelse
        </div>
    </section>

    {{-- MODAL: Términos y Condiciones --}}
    <section x-show="vistaActual === 'terminos'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="vistaActual='dashboard'"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100">
            <div class="bg-gradient-to-r from-[#07321e] to-[#0d4a2e] px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/15 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#c2d500]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Paso 1 de 2 — Términos y Condiciones</h2>
                        <p class="text-[12px] text-white/70">Revisa y acepta los compromisos del evaluador</p>
                    </div>
                </div>
                {{-- Progress --}}
                <div class="mt-4 flex gap-2">
                    <div class="h-1.5 flex-1 rounded-full bg-[#c2d500]"></div>
                    <div class="h-1.5 flex-1 rounded-full bg-white/20"></div>
                </div>
            </div>
            <div class="p-6">
                <div class="h-52 overflow-y-auto border border-gray-100 rounded-xl p-4 mb-5 bg-gray-50 text-sm text-gray-600 space-y-3 pr-3">
                    <p><strong class="text-gray-900">1. Confidencialidad</strong><br>El evaluador se compromete a mantener estricta confidencialidad sobre la información, datos y resultados de los trabajos de grado revisados, absteniéndose de divulgarlos sin autorización previa.</p>
                    <p><strong class="text-gray-900">2. Imparcialidad y Objetividad</strong><br>La evaluación debe realizarse con plena objetividad, basada exclusivamente en los criterios de la rúbrica institucional, sin favoritismos ni conflictos de interés.</p>
                    <p><strong class="text-gray-900">3. Propiedad Intelectual</strong><br>El evaluador reconoce que los trabajos son propiedad intelectual de sus autores y de la Corporación Universitaria del Caribe – CECAR, y no podrá utilizarlos para fines distintos a la evaluación.</p>
                    <p><strong class="text-gray-900">4. Cumplimiento de Plazos</strong><br>El evaluador se compromete a emitir su calificación dentro del plazo límite establecido por la institución.</p>
                    <p><strong class="text-gray-900">5. Responsabilidad</strong><br>La calificación emitida tiene carácter oficial y académico, y el evaluador asume plena responsabilidad sobre su contenido y veracidad.</p>
                </div>
                <label class="flex items-start gap-3 mb-6 cursor-pointer">
                    <input type="checkbox" id="acceptTerminos" x-model="terminosAceptados"
                        class="mt-0.5 w-5 h-5 rounded border-gray-300 text-[#c2d500] focus:ring-[#c2d500] shrink-0">
                    <span class="text-sm font-medium text-gray-700">He leído, comprendido y acepto todos los términos y condiciones del proceso de evaluación.</span>
                </label>
                <div class="flex justify-end gap-3">
                    <button @click="vistaActual='dashboard'"
                        class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-all">
                        Cancelar
                    </button>
                    <button @click="terminosAceptados && (vistaActual='datos')" :disabled="!terminosAceptados"
                        class="px-6 py-2.5 rounded-xl text-sm font-bold text-[#07321e] transition-all"
                        :class="terminosAceptados ? 'bg-[#c2d500] hover:bg-[#b6c900]' : 'bg-gray-200 text-gray-600 cursor-not-allowed'">
                        Siguiente →
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- MODAL: Tratamiento de Datos --}}
    <section x-show="vistaActual === 'datos'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="vistaActual='terminos'"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100">
            <div class="bg-gradient-to-r from-[#07321e] to-[#0d4a2e] px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/15 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#c2d500]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Paso 2 de 2 — Tratamiento de Datos</h2>
                        <p class="text-[12px] text-white/70">Autorización conforme a la Ley 1581 de 2012</p>
                    </div>
                </div>
                {{-- Progress --}}
                <div class="mt-4 flex gap-2">
                    <div class="h-1.5 flex-1 rounded-full bg-[#c2d500]/50"></div>
                    <div class="h-1.5 flex-1 rounded-full bg-[#c2d500]"></div>
                </div>
            </div>
            <div class="p-6">
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                    <div class="flex gap-2 mb-2">
                        <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-xs font-bold text-amber-700 uppercase tracking-wide">Política de Tratamiento de Datos Personales</span>
                    </div>
                    <p class="text-sm text-amber-800 leading-relaxed">
                        De conformidad con la <strong>Ley Estatutaria 1581 de 2012</strong> y el Decreto 1377 de 2013, autorizo a la <strong>Corporación Universitaria del Caribe – CECAR</strong> para recolectar, almacenar, usar y procesar mis datos personales con fines académicos e institucionales relacionados con el proceso de evaluación de trabajos de grado. Los datos serán tratados con total seguridad y no serán cedidos a terceros sin su consentimiento, salvo exigencia legal.
                    </p>
                </div>
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-3 mb-5">
                    <p class="text-xs text-gray-500">Responsable del tratamiento: CECAR — Sincelejo, Sucre. Para ejercer sus derechos de acceso, corrección o supresión de datos, puede contactar a la Oficina de Sistemas o al correo institucional.</p>
                </div>
                <label class="flex items-start gap-3 mb-6 cursor-pointer">
                    <input type="checkbox" id="acceptDatos" x-model="datosAceptados"
                        class="mt-0.5 w-5 h-5 rounded border-gray-300 text-[#c2d500] focus:ring-[#c2d500] shrink-0">
                    <span class="text-sm font-medium text-gray-700">Autorizo el tratamiento de mis datos personales según la política descrita.</span>
                </label>
                <div class="flex justify-end gap-3">
                    <button @click="vistaActual='terminos'"
                        class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-all">
                        ← Atrás
                    </button>
                    <button type="button" @click="irAEvaluacion()" :disabled="!datosAceptados || cargandoEvaluacion"
                        class="px-6 py-2.5 rounded-xl text-sm font-bold text-[#07321e] transition-all inline-flex items-center justify-center gap-2 min-w-[180px]"
                        :class="datosAceptados && !cargandoEvaluacion ? 'bg-[#c2d500] hover:bg-[#b6c900]' : 'bg-gray-200 text-gray-600 cursor-not-allowed'">
                        <template x-if="cargandoEvaluacion">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 1116 0 8 8 0 01-16 0zm8-4v4l2.5 2.5" />
                            </svg>
                        </template>
                        <span x-text="cargandoEvaluacion ? 'Cargando...' : 'Iniciar Evaluación →'" ></span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- Evaluación movida a página separada --}}
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('evaluadorFlow', () => ({
            vistaActual: 'dashboard',
            terminosAceptados: false,
            datosAceptados: false,
            trabajoTituloSeleccionado: '',
            trabajoPdfUrl: '',
            selectedTrabajoId: null,
            cargandoEvaluacion: false,

            iniciarEvaluacion(id, titulo, pdfUrl, aceptoTerminos) {
                if (aceptoTerminos) {
                    sessionStorage.setItem('evaluacion_pdf_url', pdfUrl);
                    sessionStorage.setItem('evaluacion_titulo', titulo);
                    window.location.href = '{{ url("evaluador/evaluacion") }}/' + id;
                    return;
                }
                this.selectedTrabajoId = id;
                this.trabajoTituloSeleccionado = titulo;
                this.trabajoPdfUrl = pdfUrl;
                this.terminosAceptados = false;
                this.datosAceptados = false;
                this.vistaActual = 'terminos';
            },

            irAEvaluacion() {
                if (!this.datosAceptados && this.selectedTrabajoId !== null) {
                    return;
                }

                const trabajoId = this.selectedTrabajoId;
                const titulo = this.trabajoTituloSeleccionado;
                const pdfUrl = this.trabajoPdfUrl;

                if (trabajoId === null) {
                    return;
                }

                this.cargandoEvaluacion = true;

                const self = this;
                fetch('{{ route("evaluador.aceptar-terminos") }}', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                    },
                    body: JSON.stringify({ terminos_aceptados: true, datos_aceptados: true, trabajo_id: trabajoId }),
                })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('No se pudo aceptar los términos');
                    }
                    return response.json();
                })
                .then(() => {
                    sessionStorage.setItem('evaluacion_pdf_url', pdfUrl);
                    sessionStorage.setItem('evaluacion_titulo', titulo);
                    window.location.assign('{{ url("evaluador/evaluacion") }}/' + trabajoId);
                })
                .catch(() => {
                    sessionStorage.setItem('evaluacion_pdf_url', pdfUrl);
                    sessionStorage.setItem('evaluacion_titulo', titulo);
                    window.location.assign('{{ url("evaluador/evaluacion") }}/' + trabajoId);
                });
            }
        }))
    });
</script>
@endpush
