@extends('layouts.baseAdmin')

@section('title', 'Trabajos de Grado | Panel Admin')
@section('meta_description', 'Listado y gestión de todos los trabajos de grado registrados. Filtra, busca y asigna evaluadores desde este panel.')

@section('content')
<x-notification type="success" />

<div x-data="trabajosAdmin()">


<div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 mb-6">
    <div class="flex flex-col sm:flex-row items-center gap-3">
        <div class="relative flex-1 w-full flex items-center gap-2">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" id="codigoSearch"
                    placeholder="Buscar por Código de Proyecto (ej: PGTG-001-26)..."
                    class="block w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#c2d500] focus:border-[#c2d500] focus:bg-white transition-all font-medium">
            </div>
            <button id="clearSearch" type="button"
                class="flex items-center gap-2 px-4 py-2.5 text-gray-600 bg-gray-50 border border-gray-200 rounded-xl hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all text-sm font-medium whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Limpiar
            </button>
        </div>

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     TABLA PRINCIPAL (Flowbite Table Styling)
     ═══════════════════════════════════════════════════════════════ --}}
<div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-gray-200">
    <div class="overflow-x-auto deferred-section">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="px-6 py-4 font-bold w-16">#</th>
                    <th scope="col" class="px-6 py-4 font-bold">Información del Proyecto</th>
                    <th scope="col" class="px-6 py-4 font-bold">Estudiantes</th>
                    <th scope="col" class="px-6 py-4 font-bold">Evaluadores</th>
                    <th scope="col" class="px-6 py-4 font-bold text-center">Estado</th>
                    <th scope="col" class="px-6 py-4 font-bold text-center">Fecha de Subida</th>
                    <th scope="col" class="px-6 py-4 font-bold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($trabajos as $trabajo)
                @php
                $tipo = optional($trabajo->tipo)->nombre_tipo ?? 'Sin tipo';
                $evaluadores = $trabajo->evaluadores;
                $countEval = $evaluadores->count();
                $primerEstudiante = $trabajo->estudiante->first();
                $areaTrabajo = optional($primerEstudiante)->area;
                $facultadTrabajo = optional($areaTrabajo)->facultad;
                @endphp
                <tr class="border-b transition-colors group project-row {{ $trabajo->retirado ? 'bg-rose-50/40 border-rose-300 hover:bg-rose-50/70' : 'bg-white border-gray-100 hover:bg-gray-50/80' }}"
                    data-codigo="{{ strtolower($trabajo->codigo_proyecto ?? ('id:' . $trabajo->id_trabajo)) }}">
                    {{-- # --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-xs font-bold text-gray-600">#{{ $loop->iteration }}</span>
                    </td>

                    {{-- Proyecto --}}
                    <th scope="row" class="px-6 py-4 min-w-[300px]">
                        <div class="flex flex-col gap-1.5">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 text-[10px] font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-md">
                                    {{ $trabajo->codigo_proyecto ?? ('ID: ' . $trabajo->id_trabajo) }}
                                </span>
                            </div>
                            <span class="text-sm font-bold text-gray-900 line-clamp-2 leading-snug group-hover:text-[#07321e] transition-colors project-title" title="{{ $trabajo->titulo }}">
                                {{ $trabajo->titulo }}
                            </span>
                            <div class="flex flex-wrap items-center gap-2">
                                @php
                                $badgeClasses = match($tipo) {
                                'Investigación', 'Trabajo De Grado' => 'bg-green-100 text-green-800',
                                'Emprendimiento' => 'bg-blue-100 text-blue-800',
                                'Pasantía' => 'bg-yellow-100 text-yellow-800',
                                default => 'bg-gray-100 text-gray-800'
                                };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-tight {{ $badgeClasses }}">
                                    {{ $tipo }}
                                </span>
                                @if($trabajo->tieneActa())
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-tight bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <svg class="w-2.5 h-2.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Acta Ok
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-tight bg-rose-50 text-rose-700 border border-rose-200">
                                    <svg class="w-2.5 h-2.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Sin Acta
                                </span>
                                @endif
                                @php
                                    $esPropuesta = $trabajo->plantilla_rubrica === 'propuesta_de_grado';
                                    $todosEvalFinalizados = $trabajo->evaluadores->isNotEmpty() && $trabajo->evaluadores->every(fn($e) => $e->pivot->estado_revision === 'Finalizado' && empty($e->pivot->requiere_nueva_revision));
                                    $algunoFinalizado = $trabajo->evaluadores->contains(fn($e) => $e->pivot->estado_revision === 'Finalizado');
                                    $tieneEvaluadores = $trabajo->evaluadores->isNotEmpty();
                                    $algunoRechazado = $trabajo->evaluadores->contains(fn($e) => ($e->pivot->decision_evaluador ?? null) === 'rechazado');
                                    
                                    $ambosRechazan = $trabajo->evaluadores->count() >= 2 && $trabajo->evaluadores->every(function ($eval) use ($trabajo) {
                                         $evalData = $trabajo->evaluaciones->where('id_profesor', $eval->id_profesor)->first();
                                         return $evalData && in_array(strtolower(trim($evalData->resultado ?? '')), ['rechazada', 'no_sustentar', 'requiere_reestructurar']);
                                     });
                                     $algunaMejoraRechazo = $trabajo->evaluaciones->contains(function ($eval) {
                                         return !in_array(strtolower(trim($eval->resultado ?? '')), ['aceptada', 'puede_sustentar']);
                                     });
                                     $puedeSubirInformeFinal = $todosEvalFinalizados && $esPropuesta && !$algunaMejoraRechazo && ($trabajo->estado ?? null) !== 'rechazada';
                                     $propuestaRechazada = $esPropuesta && $todosEvalFinalizados && $ambosRechazan && ($trabajo->estado ?? null) !== 'rechazada';
                                     $requiereNuevaVersion = $todosEvalFinalizados && !$ambosRechazan && $algunaMejoraRechazo && ($trabajo->estado ?? null) !== 'rechazada';

                                    $estadoProcesoAdmin = match(true) {
                                        ($trabajo->estado ?? null) === 'finalizado' => ['label' => 'Finalizado', 'class' => 'bg-teal-50 text-teal-700 border-teal-200'],
                                        $algunoRechazado => ['label' => 'Rechazado por evaluador', 'class' => 'bg-red-50 text-red-700 border-red-200'],
                                        ($trabajo->estado ?? null) === 'rechazada' => ['label' => 'Rechazada', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'],
                                        $puedeSubirInformeFinal => ['label' => 'Esperando Inf. Final', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
                                        ($trabajo->estado ?? null) === 'version_corregida_subida' => ['label' => 'Corrección Subida', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
                                        ($trabajo->estado ?? null) === 'en_revision' => ['label' => 'En revisión', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
                                        ($todosEvalFinalizados && ($requiereNuevaVersion || $propuestaRechazada)) => ['label' => 'En revisión', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
                                        $todosEvalFinalizados => ['label' => 'Calificada', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                                        $trabajo->estado === 'aprobado' => ['label' => 'Aprobado', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                                        default => ['label' => 'Sin calificar', 'class' => 'bg-gray-50 text-gray-700 border-gray-200'],
                                    };
                                @endphp
                            </div>
                        </div>
                    </th>

                    {{-- Estudiantes --}}
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-1.5">
                            @forelse($trabajo->estudiante as $est)
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-gray-700 whitespace-nowrap">{{ $est->nombre }} {{ $est->apellido }}</span>
                            </div>
                            @empty
                            <span class="text-xs text-gray-600 italic">Sin asignar</span>
                            @endforelse
                        </div>
                    </td>

                    {{-- Evaluadores --}}
                    <td class="px-6 py-4">
                        @if($countEval > 0)
                        <div class="flex flex-col gap-1.5">
                            @foreach($evaluadores as $eval)
                            @php
                                $evalRechazado = ($eval->pivot->decision_evaluador ?? null) === 'rechazado';
                            @endphp
                            <div class="flex items-center gap-2">
                                <div class="relative inline-flex items-center justify-center w-6 h-6 overflow-hidden rounded-full shrink-0 {{ $evalRechazado ? 'bg-red-100' : 'bg-[#c2d500]' }}">
                                    <span class="text-[9px] font-bold {{ $evalRechazado ? 'text-red-700' : 'text-[#07321e]' }}">{{ substr($eval->usuario->nombre, 0, 1) }}{{ substr($eval->usuario->apellido, 0, 1) }}</span>
                                </div>
                                <span class="text-xs font-bold {{ $evalRechazado ? 'text-red-600' : 'text-gray-700' }} whitespace-nowrap">{{ $eval->usuario->nombre }} {{ $eval->usuario->apellido }}</span>
                                @if($evalRechazado)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-bold bg-red-100 text-red-700">Rechazado</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @else
                        <span class="inline-flex items-center bg-red-100 text-red-800 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">
                            <span class="w-2 h-2 me-1.5 bg-red-500 rounded-full animate-pulse"></span>
                            Sin evaluadores
                        </span>
                        @endif
                    </td>

                    {{-- Estado --}}
                    <td class="px-6 py-4 text-center">
                        @if($trabajo->retirado)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[10px] font-bold uppercase tracking-tight bg-rose-100 text-rose-700 border border-rose-200 shadow-sm">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Desactivado
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-[10px] font-bold uppercase tracking-tight {{ $estadoProcesoAdmin['class'] }} border shadow-sm">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ $estadoProcesoAdmin['label'] }}
                        </span>
                        @endif
                    </td>

                    {{-- Fecha --}}
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="inline-flex items-center gap-1.5 text-xs text-gray-500 font-medium">
                            <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ \Carbon\Carbon::parse($trabajo->fecha_subida)->format('d/m/Y') }}
                        </div>
                    </td>

                    {{-- Acciones --}}
                    <td class="px-6 py-4 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1.5">
                            {{-- Botón de acción principal (oculto si el trabajo está retirado) --}}
                            @if(!$trabajo->retirado)
                                @if(!$trabajo->tieneActa())
                                {{-- Sin acta: guiar primero a la subida del Acta --}}
                                <a href="{{ route('admin.detallesTrabajo', $trabajo->id_trabajo) }}?ir=acta"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-xl hover:bg-amber-100 focus:ring-4 focus:outline-none focus:ring-amber-100 transition-all"
                                    data-tooltip-target="tooltip-acta-{{ $trabajo->id_trabajo }}" data-tooltip-placement="left">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12v6m0-6l-3 3m3-3l3 3M8 7h8l-4-4z" />
                                    </svg>
                                    Subir Acta
                                </a>
                                <div id="tooltip-acta-{{ $trabajo->id_trabajo }}" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-xs font-bold text-white bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
                                    Debe adjuntar el Acta antes de poder asignar evaluadores
                                    <div class="tooltip-arrow" data-popper-arrow></div>
                                </div>
                                @else
                                {{-- Con acta: Asignar/Editar evaluadores --}}
                                <a href="{{ route('admin.asignarEvaluador', $trabajo->id_trabajo) }}"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-[#07321e] bg-[#c2d500]/15 border border-[#c2d500]/30 rounded-xl hover:bg-[#c2d500]/30 focus:ring-4 focus:outline-none focus:ring-[#c2d500]/20 transition-all"
                                    data-tooltip-target="tooltip-assign-{{ $trabajo->id_trabajo }}" data-tooltip-placement="left">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    {{ $countEval > 0 ? 'Editar' : 'Asignar' }}
                                </a>
                                <div id="tooltip-assign-{{ $trabajo->id_trabajo }}" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-xs font-bold text-white bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
                                    {{ $countEval > 0 ? 'Editar evaluadores asignados' : 'Asignar evaluadores a este trabajo' }}
                                    <div class="tooltip-arrow" data-popper-arrow></div>
                                </div>
                                @endif
                            @endif

                            {{-- Botón Ver Detalles (visible con evaluadores asignados o si está retirado para poder reactivarlo) --}}
                            @if($countEval > 0 || $trabajo->retirado)
                            <a href="{{ route('admin.detallesTrabajo', $trabajo->id_trabajo) }}"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-gray-600 hover:text-[#07321e] hover:bg-gray-100 rounded-xl focus:ring-4 focus:outline-none focus:ring-gray-200 transition-all"
                                data-tooltip-target="tooltip-view-{{ $trabajo->id_trabajo }}" data-tooltip-placement="left">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Detalles
                            </a>
                            <div id="tooltip-view-{{ $trabajo->id_trabajo }}" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-xs font-bold text-white bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
                                Ver detalles del trabajo
                                <div class="tooltip-arrow" data-popper-arrow></div>
                            </div>
                            @endif

                            @if(auth()->check() && auth()->user()->rol === 'Administrador')
                                @if(!$trabajo->retirado)
                                {{-- Botón Desactivar (solo administrador, trabajo activo) --}}
                                <button type="button" @click="confirmarDesactivar({{ $trabajo->id_trabajo }}, '{{ addslashes($trabajo->titulo) }}')"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-gray-600 hover:text-amber-600 hover:bg-amber-50 rounded-xl focus:ring-4 focus:outline-none focus:ring-amber-100 transition-all"
                                    title="Desactivar trabajo">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                    </svg>
                                    Desactivar
                                </button>
                                @else
                                {{-- Botón Eliminar (solo administrador, trabajo desactivado) --}}
                                <button type="button" @click="confirmarEliminar({{ $trabajo->id_trabajo }}, '{{ addslashes($trabajo->titulo) }}')"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-gray-600 hover:text-rose-600 hover:bg-rose-50 rounded-xl focus:ring-4 focus:outline-none focus:ring-rose-100 transition-all"
                                    title="Eliminar trabajo">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Eliminar
                                </button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-gray-500 mb-1">No hay trabajos registrados</p>
                            <p class="text-xs text-gray-600">Los trabajos aparecerán aquí una vez sean cargados al sistema</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         PAGINACIÓN (Flowbite Pagination Style)
         ═══════════════════════════════════════════════════════════════ --}}
    <nav id="paginationContainer" class="flex flex-col md:flex-row items-center justify-between px-6 py-4 border-t border-gray-200 bg-white gap-3" aria-label="Paginación de trabajos">
        {{-- Info text --}}
        <span class="text-sm text-gray-700 font-medium">
            Mostrando <span class="font-bold text-gray-900" id="startRange">0</span> a
            <span class="font-bold text-gray-900" id="endRange">0</span> de
            <span class="font-bold text-gray-900" id="totalItems">0</span> resultados
        </span>

        {{-- Page buttons --}}
        <ul class="inline-flex items-center -space-x-px h-9 text-sm">
            <li>
                <button id="prevBtn" class="flex items-center justify-center px-3 h-9 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <span class="sr-only">Anterior</span>
                    <svg class="w-3 h-3 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4" />
                    </svg>
                </button>
            </li>
            <li id="pageNumbers" class="contents"></li>
            <li>
                <button id="nextBtn" class="flex items-center justify-center px-3 h-9 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <span class="sr-only">Siguiente</span>
                    <svg class="w-3 h-3 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4" />
                    </svg>
                </button>
            </li>
        </ul>
    </nav>
</div>

{{-- ═══ MODAL: Confirmar Eliminación ═══ --}}
<div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full border border-gray-100 overflow-hidden"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-rose-50 px-6 py-4 border-b border-rose-100 flex items-center gap-3">
            <div class="w-10 h-10 bg-rose-100 rounded-full flex items-center justify-center text-rose-600 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-rose-900">Eliminar Trabajo de Grado</h3>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm text-gray-600 leading-relaxed">
                ¿Estás seguro de que deseas eliminar permanentemente el trabajo <span class="font-bold text-gray-900" x-text="'«' + deleteTitulo + '»'"></span>?
                Esta acción <strong>no se puede deshacer</strong> y eliminará también sus evaluaciones, retroalimentaciones e historial.
            </p>
        </div>
        <div class="px-6 py-4 bg-gray-50 flex flex-col sm:flex-row-reverse gap-2">
            <button @click="eliminar()" :disabled="deleting"
                class="px-5 py-2.5 bg-rose-600 text-white rounded-xl font-bold text-sm hover:bg-rose-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!deleting">Sí, eliminar</span>
                <span x-show="deleting">Eliminando...</span>
            </button>
            <button @click="showDeleteModal = false"
                class="px-5 py-2.5 bg-white text-gray-700 border border-gray-200 rounded-xl font-bold text-sm hover:bg-gray-50 transition-all">
                Cancelar
            </button>
        </div>
    </div>
</div>

{{-- ═══ MODAL: Confirmar Desactivación ═══ --}}
<div x-show="showDeactivateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeactivateModal = false"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full border border-gray-100 overflow-hidden"
        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-amber-50 px-6 py-4 border-b border-amber-100 flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-amber-900">Desactivar Trabajo de Grado</h3>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm text-gray-600 leading-relaxed">
                ¿Deseas desactivar el trabajo <span class="font-bold text-gray-900" x-text="'«' + deactivateTitulo + '»'"></span>?
                Se desvincularán sus evaluadores asignados y quedará marcado para eliminación.
            </p>
        </div>
        <div class="px-6 py-4 bg-gray-50 flex flex-col sm:flex-row-reverse gap-2">
            <button @click="desactivar()" :disabled="deactivating"
                class="px-5 py-2.5 bg-amber-600 text-white rounded-xl font-bold text-sm hover:bg-amber-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!deactivating">Sí, desactivar</span>
                <span x-show="deactivating">Desactivando...</span>
            </button>
            <button @click="showDeactivateModal = false"
                class="px-5 py-2.5 bg-white text-gray-700 border border-gray-200 rounded-xl font-bold text-sm hover:bg-gray-50 transition-all">
                Cancelar
            </button>
        </div>
    </div>
</div>
</div>{{-- /x-data --}}

{{-- ═══════════════════════════════════════════════════════════════
     SCRIPTS DE FILTRADO Y PAGINACIÓN Y GRÁFICOS
     ═══════════════════════════════════════════════════════════════ --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('trabajosAdmin', () => ({
        showDeleteModal: false,
        deleteId: null,
        deleteTitulo: '',
        deleting: false,
        showDeactivateModal: false,
        deactivateId: null,
        deactivateTitulo: '',
        deactivating: false,
        confirmarEliminar(id, titulo) {
            this.deleteId = id;
            this.deleteTitulo = titulo;
            this.deleting = false;
            this.showDeleteModal = true;
        },
        eliminar() {
            if (!this.deleteId) return;
            this.deleting = true;
            fetch(`/admin/trabajo/${this.deleteId}/eliminar`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(r => { if (!r.ok) throw new Error(); return r.json(); })
            .then(d => {
                if (d.success) { window.location.reload(); }
                else { this.deleting = false; alert('Error: ' + (d.message || 'No se pudo eliminar el trabajo.')); }
            })
            .catch(() => { this.deleting = false; alert('Error al eliminar el trabajo.'); });
        },
        confirmarDesactivar(id, titulo) {
            this.deactivateId = id;
            this.deactivateTitulo = titulo;
            this.deactivating = false;
            this.showDeactivateModal = true;
        },
        desactivar() {
            if (!this.deactivateId) return;
            this.deactivating = true;
            fetch(`/admin/trabajo/${this.deactivateId}/retirar`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(r => { if (!r.ok) throw new Error(); return r.json(); })
            .then(d => {
                if (d.success) { window.location.reload(); }
                else { this.deactivating = false; alert('Error: ' + (d.message || 'No se pudo desactivar el trabajo.')); }
            })
            .catch(() => { this.deactivating = false; alert('Error al desactivar el trabajo.'); });
        }
    }));
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const codigoSearch = document.getElementById('codigoSearch');
        const clearSearch = document.getElementById('clearSearch');

        let currentPage = 1;
        const itemsPerPage = 5;
        let filteredRows = [];

        function filterAndPaginate() {
            const rows = document.querySelectorAll('.project-row');
            const query = (codigoSearch.value || '').toLowerCase().trim();

            filteredRows = [];
            rows.forEach(row => {
                const codigo = (row.dataset.codigo || '').toLowerCase();
                const matches = !query || codigo.includes(query);

                if (matches) {
                    filteredRows.push(row);
                } else {
                    row.style.display = 'none';
                }
            });

            const totalItemsVal = filteredRows.length;
            const totalPages = Math.ceil(totalItemsVal / itemsPerPage) || 1;

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = Math.min(startIndex + itemsPerPage, totalItemsVal);

            filteredRows.forEach((row, idx) => {
                if (idx >= startIndex && idx < endIndex) {
                    row.style.display = '';
                    const indexCol = row.querySelector('td span.text-gray-600');
                    if (indexCol) {
                        indexCol.textContent = `#${idx + 1}`;
                    }
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('totalItems').textContent = totalItemsVal;
            document.getElementById('startRange').textContent = totalItemsVal > 0 ? startIndex + 1 : 0;
            document.getElementById('endRange').textContent = endIndex;

            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage === totalPages;

            const pageNumbersContainer = document.getElementById('pageNumbers');
            let pageHtml = '';

            const maxPagesToShow = 5;
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
            if (endPage - startPage < maxPagesToShow - 1) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    pageHtml += `<li><span aria-current="page" class="flex items-center justify-center px-3 h-9 leading-tight text-white bg-[#07321e] border border-[#07321e] font-bold">${i}</span></li>`;
                } else {
                    pageHtml += `<li><button type="button" class="page-link-btn flex items-center justify-center px-3 h-9 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 transition-colors font-medium" data-page="${i}">${i}</button></li>`;
                }
            }
            pageNumbersContainer.innerHTML = pageHtml;

            document.querySelectorAll('.page-link-btn').forEach(btn => {
                btn.onclick = function() {
                    currentPage = parseInt(this.dataset.page);
                    filterAndPaginate();
                };
            });
        }

        document.getElementById('prevBtn').onclick = () => {
            if (currentPage > 1) {
                currentPage--;
                filterAndPaginate();
            }
        };
        document.getElementById('nextBtn').onclick = () => {
            const totalPages = Math.ceil(filteredRows.length / itemsPerPage) || 1;
            if (currentPage < totalPages) {
                currentPage++;
                filterAndPaginate();
            }
        };

        codigoSearch.addEventListener('input', () => {
            currentPage = 1;
            filterAndPaginate();
        });

        clearSearch.addEventListener('click', function() {
            codigoSearch.value = '';
            currentPage = 1;
            filterAndPaginate();
        });

        filterAndPaginate();
    });
</script>
@endpush
@endsection