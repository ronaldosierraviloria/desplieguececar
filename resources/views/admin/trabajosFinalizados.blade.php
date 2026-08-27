@extends('layouts.baseAdmin')

@section('title', 'Trabajos Finalizados | Panel Admin')
@section('meta_description', 'Listado de trabajos de grado finalizados que cuentan con Acta de Sustentación subida.')

@section('content')
<x-notification type="success" />

{{-- BUSCADOR --}}
<div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 mb-6">
    <div class="flex items-center gap-3">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <input type="text" id="finalizadosSearch"
                placeholder="Buscar por código de proyecto, título o estudiante..."
                class="block w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#07321e]/20 focus:border-[#07321e] focus:bg-white transition-all font-medium">
        </div>
    </div>
</div>

{{-- TABLA DE TRABAJOS FINALIZADOS --}}
<div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-gray-200">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="px-6 py-4 font-bold w-16">#</th>
                    <th scope="col" class="px-6 py-4 font-bold">Información del Proyecto</th>
                    <th scope="col" class="px-6 py-4 font-bold">Estudiantes</th>
                    <th scope="col" class="px-6 py-4 font-bold">Evaluadores</th>
                    <th scope="col" class="px-6 py-4 font-bold text-center">Estado</th>
                    <th scope="col" class="px-6 py-4 font-bold text-center">Documentos Finales</th>
                    <th scope="col" class="px-6 py-4 font-bold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="finalizadosTableBody">
                @forelse ($trabajos as $trabajo)
                @php
                $tipo = optional($trabajo->tipo)->nombre_tipo ?? 'Sin tipo';
                $evaluadores = $trabajo->evaluadores;
                @endphp
                <tr class="border-b border-gray-100 hover:bg-gray-50/80 transition-colors finalizado-row"
                    data-search="{{ strtolower(($trabajo->codigo_proyecto ?? '') . ' ' . $trabajo->titulo . ' ' . optional($trabajo->estudiante->first())->nombre) }}">
                    {{-- # --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-xs font-bold text-gray-500">#{{ $loop->iteration }}</span>
                    </td>

                    {{-- Proyecto --}}
                    <th scope="row" class="px-6 py-4 min-w-[280px]">
                        <div class="flex flex-col gap-1.5">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 text-[10px] font-mono font-bold bg-[#07321e]/10 text-[#07321e] border border-[#07321e]/15 rounded-md">
                                    {{ $trabajo->codigo_proyecto ?? ('ID: ' . $trabajo->id_trabajo) }}
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 uppercase">
                                    {{ $tipo }}
                                </span>
                            </div>
                            <span class="text-sm font-bold text-gray-900 line-clamp-2 leading-snug" title="{{ $trabajo->titulo }}">
                                {{ $trabajo->titulo }}
                            </span>
                        </div>
                    </th>

                    {{-- Estudiantes --}}
                    <td class="px-6 py-4 min-w-[200px]">
                        @if($trabajo->estudiante && $trabajo->estudiante->count() > 0)
                            <div class="space-y-1">
                                @foreach($trabajo->estudiante as $est)
                                    <div class="text-xs font-semibold text-gray-800 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {{ $est->nombre }} {{ $est->apellido }}
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="text-xs text-gray-400 italic">No asignados</span>
                        @endif
                    </td>

                    {{-- Evaluadores --}}
                    <td class="px-6 py-4 min-w-[200px]">
                        @if($evaluadores->count() > 0)
                            <div class="space-y-1">
                                @foreach($evaluadores as $eval)
                                    <div class="text-xs font-medium text-gray-700 flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                        {{ optional($eval->usuario)->nombre ?? $eval->nombre }} {{ optional($eval->usuario)->apellido ?? $eval->apellido }}
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="text-xs text-gray-400 italic">Sin jurados</span>
                        @endif
                    </td>

                    {{-- Estado --}}
                    <td class="px-6 py-4 text-center whitespace-nowrap">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Finalizado
                        </span>
                    </td>

                    {{-- Documentos Finales --}}
                    <td class="px-6 py-4 text-center whitespace-nowrap">
                        <div class="flex items-center justify-center gap-1.5">
                            @if($trabajo->archivo_pdf)
                                <a href="{{ route('trabajo.archivo', $trabajo->id_trabajo) }}" target="_blank"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-white bg-[#07321e] rounded-lg hover:bg-[#155434] transition-all" title="Descargar Proyecto (PDF)">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    PDF
                                </a>
                            @endif
                            @if($trabajo->archivo_acta_sustentacion)
                                <a href="{{ asset($trabajo->archivo_acta_sustentacion) }}" target="_blank"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-emerald-800 bg-emerald-100 rounded-lg hover:bg-emerald-200 transition-all" title="Descargar Acta de Sustentación">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Acta Sustentación
                                </a>
                            @endif
                        </div>
                    </td>

                    {{-- Acciones --}}
                    <td class="px-6 py-4 text-right whitespace-nowrap">
                        <a href="{{ route('admin.detallesTrabajo', $trabajo->id_trabajo) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#c2d500] text-[#07321e] rounded-xl font-bold text-xs hover:bg-[#b6c900] transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Ver Detalles
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm font-bold text-gray-700">No hay trabajos finalizados registrados</p>
                            <p class="text-xs text-gray-400 mt-1">Los proyectos aparecerán aquí una vez completada la sustentación y adjuntada el acta correspondiente.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('finalizadosSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const val = e.target.value.toLowerCase().trim();
                const rows = document.querySelectorAll('.finalizado-row');
                rows.forEach(row => {
                    const text = row.getAttribute('data-search') || '';
                    row.style.display = text.includes(val) ? '' : 'none';
                });
            });
        }
    });
</script>
@endpush
@endsection
