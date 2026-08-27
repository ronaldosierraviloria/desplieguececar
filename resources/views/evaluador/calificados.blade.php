@extends('layouts.baseEvaluador')

@section('title', 'Trabajos Calificados - Panel Evaluador')
@section('meta_description', 'Historial de trabajos de grado calificados por este evaluador en CECAR.')

@push('styles')
<style>
    {{-- Entrada suave de las tarjetas al cargar la página (modern entry animation) --}}
    .calificado-card {
        opacity: 1;
        transition:
            opacity 0.55s ease-out,
            transform 0.3s ease,
            box-shadow 0.3s ease;
    }
    @starting-style {
        .calificado-card {
            opacity: 0;
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .calificado-card {
            transition-duration: 0.01s;
        }
    }
</style>
@endpush

@section('content')
@php
    // Métricas rápidas para la cabecera (seguimiento de un vistazo)
    $stats = ['total' => 0, 'aprobados' => 0, 'observaciones' => 0, 'rechazados' => 0];
    foreach ($trabajosCalificados as $__t) {
        $stats['total']++;
        $__e = $__t->evaluaciones->where('id_profesor', auth()->user()->profesor->id_profesor)->first();
        $__clave = match ($__e->resultado ?? '') {
            'aceptada', 'puede_sustentar' => 'aprobados',
            'aceptada_con_mejoras', 'sustentacion_con_correcciones' => 'observaciones',
            'rechazada', 'no_sustentar' => 'rechazados',
            default => null,
        };
        if ($__clave) $stats[$__clave]++;
    }
@endphp

<div class="max-w-7xl mx-auto">

    {{-- ── CABECERA ── --}}
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-semibold text-gray-800">Trabajos Calificados</h1>
        <p class="text-sm text-gray-500 mt-1">Trabajos donde ambos evaluadores han finalizado la evaluación.</p>
    </div>

    {{-- ── TARJETAS ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3 gap-4 xl:gap-5">
        @forelse($trabajosCalificados as $trabajo)
            @php
                $tipo_nombre = $trabajo->tipo->nombre_tipo ?? 'Sin tipo';
                $tagClass = match ($tipo_nombre) {
                    'Investigación', 'Trabajo De Grado' => 'tag-trabajo',
                    'Emprendimiento' => 'tag-emprendimiento',
                    'Pasantía' => 'tag-pasantia',
                    default => 'tag-default',
                };
                $evaluacion = $trabajo->evaluaciones->where('id_profesor', auth()->user()->profesor->id_profesor)->first();
                $fechaCalificacion = ($evaluacion && $evaluacion->updated_at)
                    ? \Carbon\Carbon::parse($evaluacion->updated_at)
                    : \Carbon\Carbon::parse($trabajo->pivot->fecha_asignacion);
                $resTexto = match ($evaluacion->resultado ?? '') {
                    'aceptada', 'puede_sustentar' => 'Aprobado',
                    'aceptada_con_mejoras', 'sustentacion_con_correcciones' => 'Con observaciones',
                    'rechazada', 'no_sustentar' => 'Rechazado',
                    default => 'Finalizado',
                };
                $resChip = match ($resTexto) {
                    'Aprobado' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'Rechazado' => 'bg-red-50 text-red-700 border-red-200',
                    'Con observaciones' => 'bg-amber-50 text-amber-700 border-amber-200',
                    default => 'bg-gray-50 text-gray-600 border-gray-200',
                };
                $accent = match ($tipo_nombre) {
                    'Investigación', 'Trabajo De Grado' => 'linear-gradient(135deg, #07321e, #0a4d2e)',
                    'Emprendimiento' => 'linear-gradient(135deg, #7c3aed, #5b21b6)',
                    'Pasantía' => 'linear-gradient(135deg, #0284c7, #0369a1)',
                    default => 'linear-gradient(135deg, #475569, #334155)',
                };
            @endphp

            <div class="calificado-card group relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 ">
                {{-- Cabecera: tipo + código + estado --}}
                <div class="flex flex-wrap items-center gap-1.5 px-4 pt-4 pb-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-tight {{ $tagClass }}">{{ $tipo_nombre }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-[#07321e]/5 text-[#07321e] border border-[#07321e]/15" title="Código del Proyecto">{{ $trabajo->codigo_proyecto ?? ('ID: ' . $trabajo->id_trabajo) }}</span>
                    @if($trabajo->plantilla_rubrica === 'propuesta_de_grado')
                        @php
                            $esAceptada = $evaluacion && strtolower(trim($evaluacion->resultado)) === 'aceptada';
                        @endphp
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 ml-auto">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $esAceptada ? 'Esperando informe Final' : 'Esperando Doc. Corregido' }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200 ml-auto">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Calificado
                        </span>
                    @endif
                </div>

                {{-- Título + Nota final --}}
                <div class="flex items-start gap-3 px-4 mt-1">
                    <h3 class="flex-1 text-[13px] font-bold text-gray-900 leading-snug line-clamp-2 group-hover:text-[#07321e] transition-colors" title="{{ $trabajo->titulo }}">{{ $trabajo->titulo }}</h3>
                    @if($evaluacion && $evaluacion->nota_final !== null)
                        <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-full text-white shadow-sm" style="background: {{ $accent }};">
                            <span class="text-lg font-black leading-none">{{ number_format($evaluacion->nota_final, 1) }}</span>
                            <span class="mt-0.5 text-[8px] font-bold uppercase tracking-wider opacity-90">Nota</span>
                        </div>
                    @endif
                </div>

                {{-- Datos: estudiantes, directores, jurados --}}
                <div class="px-4 mt-4 space-y-3.5">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-600">Estudiantes</p>
                        <div class="flex flex-wrap gap-1.5 mt-1.5">
                            @forelse($trabajo->estudiante as $est)
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-100 bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gray-200 text-[8px] font-bold text-gray-600">{{ substr($est->nombre, 0, 1) }}{{ substr($est->apellido, 0, 1) }}</span>
                                {{ $est->nombre }} {{ $est->apellido }}
                                @if(!empty($est->area) && !empty($est->area->nombre_area))
                                    <span class="text-gray-600">· {{ $est->area->nombre_area }}</span>
                                @endif
                            </span>
                            @empty
                            <span class="text-[10px] text-gray-600 italic">Sin estudiantes</span>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-600">Directores</p>
                        <div class="flex flex-wrap gap-1.5 mt-1.5">
                            @forelse($trabajo->directores as $dir)
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-2 py-2 text-xs font-medium text-indigo-700">
                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5z"/></svg>
                                {{ $dir->nombre }} {{ $dir->apellido }}
                            </span>
                            @empty
                            <span class="text-[10px] text-gray-600 italic">Sin directores</span>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-600">Jurados</p>
                        <div class="flex flex-wrap gap-1.5 mt-1.5 pb-4">
                            @forelse($trabajo->evaluadores as $eval)
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2 py-2 text-xs font-medium text-emerald-700">
                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                {{ $eval->nombre }} {{ $eval->apellido }}
                            </span>
                            @empty
                            <span class="text-[10px] text-gray-600 italic">Sin jurados</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Pie: fecha + resultado + acciones --}}
                <div class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 bg-gray-50/80 px-4 py-2.5">
                    <div class="flex flex-wrap items-center gap-2 min-w-0">
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-gray-600">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Calificado: {{ $fechaCalificacion->format('d/m/Y') }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $resChip }}">{{ $resTexto }}</span>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <a href="{{ route('trabajo.archivo', $trabajo->id_trabajo) }}" target="_blank"
                            class="inline-flex items-center gap-1 px-2 py-1.5 text-[10px] font-bold text-white bg-[#07321e] rounded-md hover:bg-[#1a4d2e] transition-all" title="Descargar PDF del trabajo">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            PDF
                        </a>
                        <a href="{{ route('evaluador.rubrica-pdf', $trabajo->id_trabajo) }}" target="_blank"
                            class="inline-flex items-center gap-1 px-2 py-1.5 text-[10px] font-bold text-gray-600 bg-white border border-gray-200 rounded-md hover:bg-gray-100 hover:text-[#07321e] transition-all" title="Descargar rúbrica de evaluación (PDF)">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Rúbrica
                        </a>
                        <a href="{{ route('evaluador.detalles-evaluacion', $trabajo->id_trabajo) }}"
                            class="inline-flex items-center gap-1 px-2 py-1.5 text-[10px] font-bold text-[#07321e] bg-[#c2d500] rounded-md hover:bg-[#b6c900] transition-all">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Detalles
                        </a>
                    </div>
                </div>
            </div>
        @empty
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 px-6 py-16 text-center flex flex-col items-center lg:col-span-2 2xl:col-span-3">
            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Sin trabajos calificados</h3>
            <p class="text-sm text-gray-500 mt-1 max-w-sm">Los trabajos que califiques completamente aparecerán aquí.</p>
        </div>
        @endforelse
    </div>

</div>
@endsection
