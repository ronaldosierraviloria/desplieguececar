@extends('layouts.baseAdmin')

@section('title', 'Evaluadores por Área | Panel Admin')
@section('meta_description', 'Gestión de profesores evaluadores organizados por su Área de conocimiento y Facultad.')

@section('content')
<x-notification type="success" />
<x-notification type="error" />

<div x-data='{
    showAddEvaluadorModal: false,
    selectedFacultad: "",
    selectedArea: "",
    loading: false,
    areas: @json($areas->map(fn($a) => ["id" => $a->id_area, "nombre" => $a->nombre_area, "id_facultad" => $a->id_facultad])->values()),
    get filteredAreas() {
        if (!this.selectedFacultad) return [];
        return this.areas.filter(a => Number(a.id_facultad) === Number(this.selectedFacultad));
    }
}'>
    {{-- BUSCADOR Y BOTÓN AÑADIR EVALUADOR --}}
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 mb-6">
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full flex items-center gap-2">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text" id="evaluadoresSearch"
                        placeholder="Buscar por nombre, correo o área de conocimiento..."
                        class="block w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 focus:bg-white transition-all font-medium">
                </div>
                <button type="button" id="btnSearchEvaluadores"
                    class="px-5 py-2.5 bg-[#07321e] text-white rounded-xl font-bold text-sm hover:bg-[#07321e]/90 transition-all flex items-center gap-2 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Buscar
                </button>
            </div>

            <button @click="showAddEvaluadorModal = true; selectedFacultad = ''; selectedArea = ''" type="button"
                class="w-full sm:w-auto flex items-center justify-center gap-2 px-5 py-2.5 bg-[#c2d500] text-[#07321e] rounded-xl font-bold text-sm hover:bg-[#b6c900] transition-all active:scale-95 whitespace-nowrap shrink-0 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Añadir Evaluador
            </button>
        </div>
    </div>

    {{-- SECCIONES AGRUPADAS POR ÁREA --}}
    <div class="space-y-6" id="evaluadoresContainer">
        @forelse($evaluadoresPorArea as $nombreArea => $profesoresGrupo)
        @php
            $primerProfesor = $profesoresGrupo->first();
            $nombreFacultad = optional(optional($primerProfesor->area)->facultad)->nombre_facultad ?? 'Facultad no especificada';
        @endphp
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden area-card deferred-section" data-area="{{ strtolower($nombreArea . ' ' . $nombreFacultad) }}" x-data="{ openArea: false }">
            {{-- Encabezado del Área (Acordeón colapsable por defecto) --}}
            <div class="bg-gray-50/80 hover:bg-gray-100/80 transition-colors cursor-pointer px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3 select-none"
                @click="openArea = !openArea">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 font-bold flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-base font-bold text-gray-900 truncate">{{ $nombreArea }}</h2>
                        <p class="text-xs text-gray-500 font-medium truncate">{{ $nombreFacultad }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                        {{ $profesoresGrupo->count() }} {{ $profesoresGrupo->count() == 1 ? 'Evaluador' : 'Evaluadores' }}
                    </span>

                    <button type="button" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-gray-600 bg-white border border-gray-200 shadow-2xs hover:bg-gray-50 transition-all"
                        @click.stop="openArea = !openArea">
                        <span x-text="openArea ? 'Ocultar' : 'Mostrar'"></span>
                        <svg class="w-4 h-4 transform transition-transform duration-200" :class="openArea ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Tabla de evaluadores por área (Colapsada por defecto) --}}
            <div x-show="openArea" x-collapse x-cloak class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-[11px] text-gray-600 uppercase bg-gray-50/40 border-b border-gray-100 font-bold">
                        <tr>
                            <th scope="col" class="px-6 py-3">Docente Evaluador</th>
                            <th scope="col" class="px-6 py-3">Correo Electrónico</th>
                            <th scope="col" class="px-6 py-3 text-center">Trabajos Asignados</th>
                            <th scope="col" class="px-6 py-3 text-center">Evaluaciones Completadas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($profesoresGrupo as $profesor)
                        @php
                            $user = $profesor->usuario;
                            $nombreDocente = $user ? ($user->nombre . ' ' . $user->apellido) : 'Docente Sin Nombre';
                            $correoDocente = $user ? $user->correo : 'Sin correo';
                            $trabajosCount = $profesor->trabajos ? $profesor->trabajos->count() : 0;
                            $evalsCompletadas = $profesor->trabajos ? $profesor->trabajos->filter(function($t) use ($profesor) {
                                return $t->evaluaciones->where('id_profesor', $profesor->id_profesor)->where('evaluacion_completada', true)->count() > 0;
                            })->count() : 0;
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition-colors evaluador-item" data-search="{{ strtolower($nombreDocente . ' ' . $correoDocente . ' ' . $nombreArea) }}">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-[#07321e] text-white flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ substr($user->nombre ?? 'E', 0, 1) }}{{ substr($user->apellido ?? 'V', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">{{ $nombreDocente }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold text-gray-700 whitespace-nowrap">
                                {{ $correoDocente }}
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $trabajosCount > 0 ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">
                                    {{ $trabajosCount }} / 3 asignados
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $evalsCompletadas > 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">
                                    {{ $evalsCompletadas }} finalizadas
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center text-gray-500">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2M17 9V7a2 2 0 00-2-2H9a2 2 0 00-2 2v2m6 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-sm font-bold text-gray-700">No hay evaluadores registrados</p>
            <p class="text-xs text-gray-400 mt-1">Los docentes asignados como evaluadores aparecerán agrupados por área aquí.</p>
        </div>
        @endforelse
    </div>

    {{-- MODAL PARA REGISTRAR NUEVO EVALUADOR --}}
    <div x-show="showAddEvaluadorModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showAddEvaluadorModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showAddEvaluadorModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showAddEvaluadorModal" class="inline-block align-bottom bg-[#f4f4f4] rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
                <form action="{{ route('admin.usuarios.store') }}" method="POST" @submit="loading = true">
                    @csrf
                    <input type="hidden" name="rol" value="Evaluador">
                    
                    <div class="bg-[#07321e] px-6 py-4">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-white">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-white">Registrar Nuevo Evaluador</h3>
                                    <p class="text-xs text-white/70">Asigna las credenciales y el área de especialidad del docente evaluador</p>
                                </div>
                            </div>
                            <button type="button" @click="showAddEvaluadorModal = false" class="text-white/70 hover:text-white transition duration-150 p-2 hover:bg-white/10 rounded-lg">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nombre</label>
                                <input type="text" name="nombre" placeholder="Ej: Carlos" class="block bg-white w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#c2d500] focus:border-transparent outline-none placeholder-gray-400 font-medium" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Apellido</label>
                                <input type="text" name="apellido" placeholder="Ej: Gómez" class="block bg-white w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#c2d500] focus:border-transparent outline-none placeholder-gray-400 font-medium" required>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Correo Electrónico Inst.</label>
                            <input type="email" name="correo" placeholder="Ej: carlos.gomez@cecar.edu.co" class="block bg-white w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#c2d500] focus:border-transparent outline-none placeholder-gray-400 font-medium" required>
                        </div>

                        <div class="mb-6">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Facultad</label>
                            <select x-model="selectedFacultad" class="block bg-white w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#c2d500] focus:border-transparent outline-none font-medium" required>
                                <option value="">Seleccione una facultad...</option>
                                @foreach($facultades as $facultad)
                                    <option value="{{ $facultad->id_facultad }}">{{ $facultad->nombre_facultad }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-6" x-show="selectedFacultad" x-transition>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Área de Especialidad (Requerida)</label>
                            <select name="id_area" x-model="selectedArea" :required="selectedFacultad ? true : false" :disabled="!selectedFacultad" class="block bg-white w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#c2d500] focus:border-transparent outline-none font-medium">
                                <option value="">Seleccione un área...</option>
                                <template x-for="area in filteredAreas" :key="area.id">
                                    <option :value="area.id" x-text="area.nombre"></option>
                                </template>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8" x-data="{ showPassword: false, showConfirmPassword: false }">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Contraseña</label>
                                <div class="relative">
                                    <input :type="showPassword ? 'text' : 'password'" name="contraseña" placeholder="Contraseña..." class="block bg-white w-full px-4 py-3 pr-12 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#c2d500] focus:border-transparent outline-none placeholder-gray-400 font-medium" required>
                                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-600 hover:text-gray-700 rounded-lg">
                                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Confirmar Contraseña</label>
                                <div class="relative">
                                    <input :type="showConfirmPassword ? 'text' : 'password'" name="contraseña_confirmation" placeholder="Contraseña..." class="block bg-white w-full px-4 py-3 pr-12 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#c2d500] focus:border-transparent outline-none placeholder-gray-400 font-medium" required>
                                    <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-600 hover:text-gray-700 rounded-lg">
                                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row-reverse gap-3 pt-4 border-t border-gray-100">
                            <button type="submit" :disabled="loading" class="px-8 py-3 rounded-xl font-bold text-center hover:bg-[#07321e]/90 transition-all w-full sm:w-auto bg-[#07321e] text-white">
                                <span x-show="!loading">Registrar Evaluador</span>
                                <span x-show="loading">Procesando...</span>
                            </button>
                            <button type="button" @click="showAddEvaluadorModal = false" class="px-8 py-3 rounded-xl bg-white font-bold text-center border border-gray-200 text-gray-500 hover:bg-gray-100 w-full sm:w-auto text-sm">Cancelar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('evaluadoresSearch');
        const searchBtn = document.getElementById('btnSearchEvaluadores');

        function doSearch() {
            if (!searchInput) return;
            const val = searchInput.value.toLowerCase().trim();
            const items = document.querySelectorAll('.evaluador-item');
            const cards = document.querySelectorAll('.area-card');

            items.forEach(item => {
                const text = item.getAttribute('data-search') || '';
                item.style.display = text.includes(val) ? '' : 'none';
            });

            cards.forEach(card => {
                const visibleItems = card.querySelectorAll('.evaluador-item:not([style*="display: none"])');
                card.style.display = visibleItems.length > 0 ? '' : 'none';
                if (val !== '' && visibleItems.length > 0 && card._x_dataStack) {
                    card._x_dataStack[0].openArea = true;
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', doSearch);
        }
        if (searchBtn) {
            searchBtn.addEventListener('click', doSearch);
        }
    });
</script>
@endpush
@endsection
