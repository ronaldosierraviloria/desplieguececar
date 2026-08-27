@extends('layouts.baseGestor')

@section('title', 'Panel del Gestor | Proyectos')
@section('meta_description', 'Panel del Gestor: lista de trabajos de grado registrados, acceso a detalles, rúbricas y subida de informes.')

@section('content')
<x-notification type="success" />

<div x-data="trabajoApp()">
@php
    $total        = $trabajos->count();
    $calificados  = $trabajos->filter(fn($t) => $t->evaluadores->isNotEmpty() && $t->evaluadores->every(fn($e) => $e->pivot->estado_revision === 'Finalizado'))->count();
    $sinEvaluador = $trabajos->filter(fn($t) => $t->evaluadores->isEmpty())->count();
@endphp

{{-- ═══ KPIs ═══ --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    @php
    $kpis = [
        ['label' => 'Total proyectos', 'value' => $total,        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'color' => 'bg-gray-100 text-gray-600'],
        ['label' => 'Calificados',     'value' => $calificados,  'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',                                                                                     'color' => 'bg-emerald-50 text-emerald-700'],
        ['label' => 'Sin evaluador',   'value' => $sinEvaluador, 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',               'color' => 'bg-amber-50 text-amber-700'],
    ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="flex items-center gap-3 px-4 py-3.5 bg-white rounded-2xl border border-gray-200 shadow-sm">
        <div class="w-10 h-10 rounded-xl {{ $kpi['color'] }} flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $kpi['icon'] }}"/>
            </svg>
        </div>
        <div>
            <p class="text-2xl font-extrabold text-gray-900 leading-none">{{ $kpi['value'] }}</p>
            <p class="text-[11px] font-medium text-gray-500 mt-0.5">{{ $kpi['label'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- ═══ Barra de herramientas ═══ --}}
<div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 mb-6">
    <div class="flex items-center gap-3">
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

{{-- ═══ Tabla ═══ --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-4">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            {{-- Cabecera --}}
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 text-[11px] uppercase tracking-wider">
                    <th class="px-4 py-3.5 font-bold rounded-tl-2xl w-10 text-center">#</th>
                    <th class="px-4 py-3.5 font-bold min-w-[110px]">Código</th>
                    <th class="px-4 py-3.5 font-bold min-w-[240px]">Proyecto</th>
                    <th class="px-4 py-3.5 font-bold min-w-[100px]">Tipo</th>
                    <th class="px-4 py-3.5 font-bold min-w-[160px]">Estudiante(s)</th>
                    <th class="px-4 py-3.5 font-bold min-w-[140px]">Director(es)</th>
                    <th class="px-4 py-3.5 font-bold min-w-[160px]">Evaluador(es)</th>
                    <th class="px-4 py-3.5 font-bold min-w-[130px]">Estado</th>
                    <th class="px-4 py-3.5 font-bold min-w-[100px]">Subido</th>
                    <th class="px-4 py-3.5 font-bold text-right rounded-tr-2xl">Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody" class="divide-y divide-gray-100">
                @forelse ($trabajos as $trabajo)
                @php
                    $tipo = optional($trabajo->tipo)->nombre_tipo ?? 'Sin tipo';
                    $esPropuesta = $trabajo->plantilla_rubrica === 'propuesta_de_grado';
                    $todosEvalFinalizados = $trabajo->evaluadores->isNotEmpty() && $trabajo->evaluadores->every(fn($e) => $e->pivot->estado_revision === 'Finalizado' && empty($e->pivot->requiere_nueva_revision));
                    $algunoFinalizado = $trabajo->evaluadores->contains(fn($e) => $e->pivot->estado_revision === 'Finalizado');
                    $algunaMejoraRechazo = $trabajo->evaluaciones->contains(function ($eval) {
                        return !in_array(strtolower(trim($eval->resultado ?? '')), ['aceptada', 'puede_sustentar']);
                    });
                    $ambosRechazan = $trabajo->evaluaciones->count() > 0 && $trabajo->evaluaciones->every(function ($eval) {
                        return in_array(strtolower(trim($eval->resultado ?? '')), ['rechazada', 'no_sustentar', 'requiere_reestructurar']);
                    });
                    
                    $puedeSubirInformeFinal = $todosEvalFinalizados && $esPropuesta && !$algunaMejoraRechazo && ($trabajo->estado ?? null) !== 'rechazada';
                    $propuestaRechazada = $esPropuesta && $todosEvalFinalizados && $ambosRechazan && ($trabajo->estado ?? null) !== 'rechazada';
                    $requiereNuevaVersion = $todosEvalFinalizados && !$ambosRechazan && $algunaMejoraRechazo && ($trabajo->estado ?? null) !== 'rechazada';
                    $estado = $trabajo->estado ?? 'subido';
                    if ($estado === 'rechazada')        $estado = 'rechazada';
                    elseif ($estado === 'finalizado')   $estado = 'finalizado';
                    elseif ($puedeSubirInformeFinal)    $estado = 'esperando_informe_final';
                    elseif (!$todosEvalFinalizados && $estado === 'version_corregida_subida') $estado = 'version_corregida_subida';
                    elseif (!$todosEvalFinalizados && $estado === 'en_revision')  $estado = 'en_revision';
                    elseif ($propuestaRechazada || $requiereNuevaVersion) $estado = 'en_revision';
                    elseif ($todosEvalFinalizados)      $estado = 'calificada';
                    else                                $estado = 'sin_calificar';

                    $estadoLabels  = ['sin_calificar' => 'Sin Calificar', 'subido' => 'Subido','en_revision' => 'En Revisión','retroalimentacion_emitida' => 'Retro.','version_corregida_subida' => 'Corregido','aprobado' => 'Aprobado','calificada' => 'Calificada','esperando_informe_final' => 'Esperando Inf. Final','esperando' => 'Esperando','rechazada' => 'Rechazada','finalizado' => 'Finalizado'];
                    $estadoColors  = ['sin_calificar' => 'bg-gray-50 text-gray-600 border-gray-200', 'subido' => 'bg-sky-50 text-sky-700 border-sky-200','en_revision' => 'bg-blue-50 text-blue-700 border-blue-200','retroalimentacion_emitida' => 'bg-violet-50 text-violet-700 border-violet-200','version_corregida_subida' => 'bg-indigo-50 text-indigo-700 border-indigo-200','aprobado' => 'bg-emerald-50 text-emerald-700 border-emerald-200','calificada' => 'bg-emerald-50 text-emerald-700 border-emerald-200','esperando_informe_final' => 'bg-amber-50 text-amber-700 border-amber-200','esperando' => 'bg-amber-50 text-amber-700 border-amber-200','rechazada' => 'bg-rose-50 text-rose-700 border-rose-200','finalizado' => 'bg-teal-50 text-teal-700 border-teal-200'];
                    $estadoIcons   = ['sin_calificar' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'subido' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12','en_revision' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z','retroalimentacion_emitida' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z','version_corregida_subida' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15','aprobado' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z','calificada' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z','esperando_informe_final' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z','esperando' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z','rechazada' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z','finalizado' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'];

                    $tipoColors = ['Trabajo De Grado' => 'bg-[#07321e]/10 text-[#07321e] border border-[#07321e]/20','Emprendimiento' => 'bg-orange-50 text-orange-700 border border-orange-200','Pasantía' => 'bg-sky-50 text-sky-700 border border-sky-200'];

                    $searchText = strtolower(($trabajo->codigo_proyecto ?? '') . ' ' . $trabajo->titulo . ' ' .
                        $trabajo->estudiante->map(fn($e) => $e->nombre.' '.$e->apellido)->implode(' ') . ' ' .
                        $trabajo->directores->map(fn($d) => $d->nombre.' '.$d->apellido)->implode(' '));
                @endphp

                <tr class="project-row group hover:bg-[#07321e]/[0.025] transition-colors"
                    id="trabajo-{{ $trabajo->id_trabajo }}"
                    data-codigo="{{ strtolower($trabajo->codigo_proyecto ?? ('id:' . $trabajo->id_trabajo)) }}">

                    {{-- # --}}
                    <td class="px-4 py-4 text-center">
                        <span class="index-badge inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 text-gray-500 text-[11px] font-extrabold">
                            {{ $loop->iteration }}
                        </span>
                    </td>

                    {{-- Código único --}}
                    <td class="px-4 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-200" title="Código del Proyecto">
                            {{ $trabajo->codigo_proyecto ?? ('ID: ' . $trabajo->id_trabajo) }}
                        </span>
                    </td>

                    {{-- Título + badges auxiliares --}}
                    <td class="px-4 py-4">
                        <p class="font-bold text-[13px] text-gray-900 leading-snug max-w-[260px] group-hover:text-[#07321e] transition-colors" title="{{ $trabajo->titulo }}">
                            {{ Str::limit($trabajo->titulo, 65) }}
                        </p>
                        <div class="flex flex-wrap gap-1.5 mt-1.5">
                            @if($trabajo->retirado)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-gray-100 text-gray-500 border border-gray-200">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Retirado
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                    Activo
                                </span>
                            @endif

                        </div>
                    </td>

                    {{-- Tipo --}}
                    <td class="px-4 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-tight {{ $tipoColors[$tipo] ?? 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                            {{ $tipo }}
                        </span>
                    </td>

                    {{-- Estudiantes --}}
                    <td class="px-4 py-4">
                        <div class="flex flex-col gap-1.5">
                            @forelse($trabajo->estudiante as $est)
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-[#c2d500]/30 text-[#07321e] flex items-center justify-center shrink-0">
                                    <span class="text-[9px] font-extrabold">{{ substr($est->nombre,0,1) }}{{ substr($est->apellido,0,1) }}</span>
                                </div>
                                <span class="text-[12px] text-gray-800 font-medium truncate max-w-[120px]" title="{{ $est->nombre }} {{ $est->apellido }}">
                                    {{ $est->nombre }} {{ $est->apellido }}
                                </span>
                            </div>
                            @empty
                            <span class="text-xs text-gray-600 italic">Sin asignar</span>
                            @endforelse
                        </div>
                    </td>

                    {{-- Directores --}}
                    <td class="px-4 py-4">
                        <div class="flex flex-col gap-1">
                            @forelse($trabajo->directores as $dir)
                            <span class="text-[12px] text-gray-700 truncate max-w-[130px] font-medium" title="{{ $dir->nombre }} {{ $dir->apellido }}">
                                {{ $dir->nombre }} {{ $dir->apellido }}
                            </span>
                            @empty
                            <span class="text-xs text-gray-600 italic">Sin asignar</span>
                            @endforelse
                        </div>
                    </td>

                    {{-- Evaluadores --}}
                    <td class="px-4 py-4">
                        <div class="flex flex-col gap-2">
                            @forelse($trabajo->evaluadores as $ev)
                            @php $evOk = $ev->pivot->estado_revision === 'Finalizado'; @endphp
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[12px] text-gray-800 font-semibold truncate max-w-[140px]" title="{{ $ev->nombre }} {{ $ev->apellido }}">
                                    {{ $ev->nombre }} {{ $ev->apellido }}
                                </span>
                                @if($evOk)
                                <span class="text-[10px] font-bold text-emerald-600">✔ Finalizado</span>
                                @else
                                <span class="text-[10px] font-bold text-amber-500">● Pendiente</span>
                                @endif
                            </div>
                            @empty
                            <span class="text-[11px] font-medium text-rose-500 italic">Sin asignar</span>
                            @endforelse
                        </div>
                    </td>

                    {{-- Estado --}}
                    <td class="px-4 py-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[10px] font-bold border {{ $estadoColors[$estado] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $estadoIcons[$estado] ?? 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' }}"/>
                            </svg>
                            {{ $estadoLabels[$estado] ?? ucfirst($estado) }}
                        </span>
                    </td>

                    {{-- Fecha --}}
                    <td class="px-4 py-4 whitespace-nowrap">
                        <span class="text-[12px] text-gray-600 font-medium">
                            {{ \Carbon\Carbon::parse($trabajo->fecha_subida)->format('d/m/Y') }}
                        </span>
                    </td>

                    {{-- Acciones --}}
                    <td class="px-4 py-4">
                        <div class="flex items-center justify-end gap-1.5">
                            {{-- Detalles --}}
                            <a href="{{ route('gestor.trabajo.detalles', $trabajo->id_trabajo) }}"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-gray-100 text-gray-700 hover:bg-[#07321e] hover:text-white transition-all"
                                title="Ver Detalles">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Detalles
                            </a>

                            {{-- Informe Final --}}
                            @if(!$trabajo->retirado && $puedeSubirInformeFinal)
                            <a href="{{ route('gestor.trabajo.informe-final', $trabajo->id_trabajo) }}"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-[#c2d500] text-[#07321e] hover:bg-[#b5c700] transition-all"
                                title="Subir Informe Final">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Subir Informe Final
                            </a>
                            @elseif(!$trabajo->retirado && $propuestaRechazada)
                            <a href="{{ route('gestor.trabajo.detalles', $trabajo->id_trabajo) }}"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 transition-all"
                                title="Decidir continuación de la propuesta rechazada">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Decidir
                            </a>
                            @elseif(!$trabajo->retirado && $requiereNuevaVersion)
                            <a href="{{ route('gestor.trabajo.detalles', $trabajo->id_trabajo) }}"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 transition-all"
                                title="Subir archivo corregido">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Subir Corrección
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <p class="text-base font-bold text-gray-900">No hay proyectos registrados</p>
                            <p class="text-sm text-gray-600 max-w-xs">Los proyectos aparecerán aquí cuando sean registrados en el sistema.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Sin resultados tras filtrar --}}
    <div id="emptyFiltered" class="hidden py-12 text-center border-t border-gray-100">
        <div class="flex flex-col items-center gap-2">
            <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
            </svg>
            <p class="text-sm font-bold text-gray-500">Sin resultados para estos filtros</p>
            <button id="clearSearch2" class="text-xs text-[#07321e] font-bold underline hover:no-underline">Limpiar filtros</button>
        </div>
    </div>
</div>

{{-- ═══ Paginación ═══ --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
    <nav id="paginationContainer" class="flex flex-col sm:flex-row items-center justify-between px-5 py-3.5 gap-3" aria-label="Paginación de trabajos">
        <span class="text-sm text-gray-600 font-medium">
            Mostrando <span class="font-extrabold text-gray-900" id="startRange">0</span>–<span class="font-extrabold text-gray-900" id="endRange">0</span>
            de <span class="font-extrabold text-gray-900" id="totalItems">0</span> proyectos
        </span>
        <ul class="inline-flex items-center -space-x-px h-9 text-sm">
            <li>
                <button id="prevBtn" class="flex items-center justify-center px-3 h-9 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-xl hover:bg-gray-50 hover:text-[#07321e] disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    <span class="sr-only">Anterior</span>
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/></svg>
                </button>
            </li>
            <li id="pageNumbers" class="contents"></li>
            <li>
                <button id="nextBtn" class="flex items-center justify-center px-3 h-9 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-xl hover:bg-gray-50 hover:text-[#07321e] disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    <span class="sr-only">Siguiente</span>
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
                </button>
            </li>
        </ul>
    </nav>
</div>

</div>{{-- /x-data --}}
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('trabajoApp', () => ({
    }));
});

document.addEventListener('DOMContentLoaded', function () {
    const codigoSearch = document.getElementById('codigoSearch');
    const clearSearch  = document.getElementById('clearSearch');
    const clearSearch2 = document.getElementById('clearSearch2');

    let currentPage    = 1;
    const ITEMS_PER_PAGE = 5;
    let filteredRows   = [];

    function filterAndPaginate() {
        const rows = document.querySelectorAll('.project-row');
        const query = (codigoSearch.value || '').toLowerCase().trim();

        filteredRows = [];
        rows.forEach(row => {
            const codigo = (row.dataset.codigo || '').toLowerCase();
            const matches = !query || codigo.includes(query);
            
            row.style.display = matches ? '' : 'none';
            if (matches) filteredRows.push(row);
        });

        const total      = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / ITEMS_PER_PAGE));
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * ITEMS_PER_PAGE;
        const end   = Math.min(start + ITEMS_PER_PAGE, total);

        filteredRows.forEach((row, i) => {
            row.style.display = (i >= start && i < end) ? '' : 'none';
            const b = row.querySelector('.index-badge');
            if (b && i >= start && i < end) b.textContent = start + (i - start) + 1;
        });

        document.getElementById('totalItems').textContent = total;
        document.getElementById('startRange').textContent = total > 0 ? start + 1 : 0;
        document.getElementById('endRange').textContent   = end;
        document.getElementById('prevBtn').disabled = currentPage === 1;
        document.getElementById('nextBtn').disabled = currentPage === totalPages;
        document.getElementById('emptyFiltered').classList.toggle('hidden', total > 0);

        // Páginas
        const maxShow = 5;
        let sp = Math.max(1, currentPage - 2);
        let ep = Math.min(totalPages, sp + maxShow - 1);
        if (ep - sp < maxShow - 1) sp = Math.max(1, ep - maxShow + 1);

        let html = '';
        for (let p = sp; p <= ep; p++) {
            if (p === currentPage) {
                html += `<li><span class="flex items-center justify-center px-3 h-9 text-white bg-[#07321e] border border-[#07321e] font-bold">${p}</span></li>`;
            } else {
                html += `<li><button type="button" class="page-btn flex items-center justify-center px-3 h-9 text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 hover:text-[#07321e] transition-colors font-medium" data-page="${p}">${p}</button></li>`;
            }
        }
        document.getElementById('pageNumbers').innerHTML = html;
        document.querySelectorAll('.page-btn').forEach(b => {
            b.addEventListener('click', () => { currentPage = +b.dataset.page; filterAndPaginate(); });
        });
    }

    document.getElementById('prevBtn').addEventListener('click', () => { if (currentPage > 1) { currentPage--; filterAndPaginate(); } });
    document.getElementById('nextBtn').addEventListener('click', () => {
        const total = Math.max(1, Math.ceil(filteredRows.length / ITEMS_PER_PAGE));
        if (currentPage < total) { currentPage++; filterAndPaginate(); }
    });

    function resetFilters() {
        if (codigoSearch) codigoSearch.value = '';
        currentPage = 1;
        filterAndPaginate();
    }

    if (codigoSearch) codigoSearch.addEventListener('input', () => { currentPage = 1; filterAndPaginate(); });
    if (clearSearch)  clearSearch.addEventListener('click', resetFilters);
    if (clearSearch2) clearSearch2.addEventListener('click', resetFilters);

    filterAndPaginate();
});
</script>
@endpush
