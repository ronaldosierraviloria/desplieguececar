@extends('layouts.baseAdmin')

@section('title', 'Panel De Administrador | Lista de Estudiantes')
@section('meta_description', 'Listado de estudiantes registrados en el sistema de trabajos de grado de CECAR.')

@section('content')
@php
$usuario = Auth::user() ?? (object)['nombre' => 'Administrador', 'apellido' => '', 'rol' => 'Administrador'];
$estudiantesPorTrabajo = $estudiantesPorTrabajo ?? collect([]);
$facultades = $facultades ?? collect([]);
$areas = $areas ?? collect([]);
@endphp

<div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 mb-6">
    <form method="GET" action="{{ route('admin.listaEstudiantes') }}" id="filterForm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <div class="relative">
                <input type="text" name="busqueda" id="searchInput" placeholder="Buscar estudiante..."
                    value="{{ request('busqueda') }}"
                    class="block w-full pl-9 pr-4 py-2.5 bg-gray-50 border-none rounded-xl text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#c2d500] focus:bg-white transition-all shadow-inner">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-600">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
            <div class="relative">
                <select name="id_facultad" onchange="document.getElementById('filterForm').submit()"
                    class="appearance-none block w-full pl-9 pr-10 py-2.5 bg-gray-50 border-none rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#c2d500] focus:bg-white shadow-inner transition-all text-gray-600 font-medium">
                    <option value="">Todas las facultades</option>
                    @foreach($facultades as $facultad)
                    <option value="{{ $facultad->id_facultad }}" {{ request('id_facultad') == $facultad->id_facultad ? 'selected' : '' }}>{{ $facultad->nombre_facultad }}</option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-600">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
            </div>
            <div class="relative">
                <select name="id_area" onchange="document.getElementById('filterForm').submit()"
                    id="areaFilter"
                    class="appearance-none block w-full pl-9 pr-10 py-2.5 bg-gray-50 border-none rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#c2d500] focus:bg-white shadow-inner transition-all text-gray-600 font-medium"
                    {{ request('id_facultad') ? '' : 'disabled' }}>
                    <option value="">Todas las áreas</option>
                    @foreach($areas as $area)
                    <option value="{{ $area->id_area }}" {{ request('id_area') == $area->id_area ? 'selected' : '' }}>{{ $area->nombre_area }}</option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-600">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if(request('id_facultad') || request('id_area') || request('busqueda'))
                <a href="{{ route('admin.listaEstudiantes') }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-bold text-rose-600 bg-rose-50 border border-rose-200 rounded-xl hover:bg-rose-100 transition-all whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Limpiar filtros
                </a>
                @endif
            </div>
        </div>
    </form>
</div>

<x-notification type="success" />
<x-notification type="error" />

@if($trabajosPaginados->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-6 py-16 text-center">
        <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
        </svg>
        <p class="text-sm font-medium text-gray-500 italic">No hay estudiantes registrados en el sistema.</p>
    </div>
@else
<div class="space-y-6">
    @foreach($trabajosPaginados as $trabajo)
    @php
        $tipoNombre = $trabajo->tipo->nombre_tipo ?? 'Sin tipo';
        $tagClass = match($tipoNombre) {
            'Investigación', 'Trabajo De Grado' => 'bg-[#07321e]/10 text-[#07321e] border-[#07321e]/20',
            'Emprendimiento'                    => 'bg-purple-50 text-purple-700 border-purple-200',
            'Pasantía'                          => 'bg-blue-50 text-blue-700 border-blue-200',
            default                             => 'bg-gray-100 text-gray-600 border-gray-200',
        };
    @endphp

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden deferred-section" x-data="{ openEstudiantes: false }">

        {{-- Encabezado de la propuesta (Acordeón colapsable) --}}
        <div class="px-6 py-4 bg-gray-50 hover:bg-gray-100/80 transition-colors cursor-pointer border-b border-gray-200 flex flex-wrap items-center justify-between gap-3 select-none"
            @click="openEstudiantes = !openEstudiantes">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-0.5">
                        {{ $trabajo->codigo_proyecto ?? ('Propuesta #' . $trabajo->id_trabajo) }}
                    </p>
                    <span class="bg-gray-200/70 text-gray-700 text-[10px] font-bold px-2 py-0.5 rounded-full">
                        {{ $trabajo->estudiante->count() }} {{ $trabajo->estudiante->count() === 1 ? 'estudiante' : 'estudiantes' }}
                    </span>
                </div>
                <h3 class="text-sm font-bold text-gray-900 truncate" title="{{ $trabajo->titulo }}">
                    {{ $trabajo->titulo }}
                </h3>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide border {{ $tagClass }}">
                    {{ $tipoNombre }}
                </span>
                <button type="button" 
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-gray-600 bg-white border border-gray-200 shadow-2xs hover:bg-gray-50 transition-all"
                    @click.stop="openEstudiantes = !openEstudiantes">
                    <span x-text="openEstudiantes ? 'Ocultar' : 'Mostrar'"></span>
                    <svg class="w-4 h-4 transform transition-transform duration-200" :class="openEstudiantes ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Tabla de estudiantes del grupo (Colapsable) --}}
        <div x-show="openEstudiantes" x-collapse x-cloak class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider w-10 text-center">#</th>
                        <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Estudiante</th>
                        <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Contacto</th>
                        <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Facultad</th>
                        <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Área</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($trabajo->estudiante as $i => $estudiante)
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="px-6 py-3.5 text-center">
                            <span class="text-xs font-bold text-gray-400">{{ $i + 1 }}</span>
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="relative inline-flex items-center justify-center w-8 h-8 overflow-hidden bg-[#07321e]/10 rounded-full shrink-0">
                                    <span class="text-[11px] font-bold text-[#07321e]">{{ substr($estudiante->nombre, 0, 1) }}{{ substr($estudiante->apellido, 0, 1) }}</span>
                                </div>
                                <span class="text-sm font-bold text-gray-900">{{ $estudiante->nombre }} {{ $estudiante->apellido }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            <div class="flex items-center gap-1.5 text-xs text-gray-600 font-medium">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                {{ $estudiante->correo ?? 'N/A' }}
                            </div>
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-700">
                                {{ $estudiante->area->facultad->nombre_facultad ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-tight bg-[#c2d500]/10 text-[#07321e] border border-[#c2d500]/20">
                                {{ $estudiante->area->nombre_area ?? 'Sin Área' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

    {{-- Enlaces de paginación --}}
    <div class="mt-6">
        {{ $trabajosPaginados->links() }}
    </div>
</div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var facultadSelect = document.querySelector('select[name="id_facultad"]');
        var areaSelect = document.getElementById('areaFilter');

        function toggleAreaFilter() {
            var hasFacultad = facultadSelect.value !== '';
            areaSelect.disabled = !hasFacultad;
            if (!hasFacultad) {
                areaSelect.value = '';
            }
        }

        if (facultadSelect) {
            facultadSelect.addEventListener('change', toggleAreaFilter);
            toggleAreaFilter();
        }

        var searchInput = document.getElementById('searchInput');
        if (searchInput) {
            var debounceTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    document.getElementById('filterForm').submit();
                }, 600);
            });
        }
    });
</script>
@endsection