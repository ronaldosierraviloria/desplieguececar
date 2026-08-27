@extends('layouts.baseAdmin')

@section('title', 'Panel De Administrador | Comentarios')
@section('meta_description', 'Motivos de rechazos de evaluadores y eliminaciones de estudiantes.')

@section('content')
@php
$usuario = Auth::user() ?? (object)['nombre' => 'Administrador', 'apellido' => '', 'rol' => 'Administrador'];
$eventos = $eventos ?? collect([]);
@endphp

<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Comentarios</h1>
        <p class="text-sm text-gray-500 mt-1">Motivos de rechazos de evaluadores y eliminaciones de estudiantes.</p>
    </div>
</div>

<div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 mb-6">
    <form method="GET" action="{{ route('admin.historial') }}">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="relative">
                <select name="tipo"
                    onchange="this.form.submit()"
                    class="appearance-none block w-full pl-9 pr-10 py-2.5 bg-gray-50 border-none rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#c2d500] focus:bg-white shadow-inner transition-all text-gray-600 font-medium">
                    <option value="">Todos los eventos</option>
                    <option value="evaluador_rechazo" {{ request('tipo') == 'evaluador_rechazo' ? 'selected' : '' }}>Rechazo de evaluador</option>
                    <option value="estudiante_eliminado" {{ request('tipo') == 'estudiante_eliminado' ? 'selected' : '' }}>Eliminación de estudiante</option>
                </select>
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-600">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                </div>
            </div>

            <div class="relative">
                <input type="text" name="busqueda" placeholder="Buscar por proyecto o detalle..."
                    value="{{ request('busqueda') }}"
                    class="block w-full pl-9 pr-4 py-2.5 bg-gray-50 border-none rounded-xl text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#c2d500] focus:bg-white transition-all shadow-inner">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-600">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                    class="px-5 py-2.5 bg-[#c2d500] text-[#07321e] rounded-xl font-bold text-sm hover:bg-[#b6c900] transition-all">
                    Filtrar
                </button>
                @if(request('tipo') || request('busqueda'))
                <a href="{{ route('admin.historial') }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-bold text-rose-600 bg-rose-50 border border-rose-200 rounded-xl hover:bg-rose-100 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Limpiar
                </a>
                @endif
            </div>
        </div>
    </form>
</div>

<x-notification type="success" />
<x-notification type="error" />

<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
    @if($eventos->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Proyecto</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Detalle</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Realizado por</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($eventos as $evento)
                <tr class="hover:bg-gray-50/80 transition-all duration-200">
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($evento->estado === 'evaluador_rechazo')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 text-xs font-bold">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Rechazo de evaluador
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 text-xs font-bold">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6z" />
                            </svg>
                            Eliminación de estudiante
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($evento->trabajo)
                        <a href="{{ route('admin.detallesTrabajo', $evento->trabajo->id_trabajo) }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 hover:underline">
                            {{ $evento->trabajo->titulo }}
                        </a>
                        @else
                        <span class="text-sm text-gray-600">Proyecto eliminado</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-sm text-gray-700 max-w-md">{{ $evento->observacion_estado }}</p>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-800">
                            {{ $evento->usuario ? $evento->usuario->nombre . ' ' . $evento->usuario->apellido : 'Sistema' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-500">{{ $evento->created_at->format('d/m/Y H:i') }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $eventos->links() }}
    </div>
    @else
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-1">No hay eventos registrados</h3>
        <p class="text-sm text-gray-500">Aún no se han producido rechazos de evaluadores ni eliminaciones de estudiantes.</p>
    </div>
    @endif
</div>
@endsection
