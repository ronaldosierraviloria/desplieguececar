@extends($layout)

@section('title', 'Notificaciones')
@section('meta_description', 'Panel de notificaciones del Sistema de Gestión de Trabajos de Grado de CECAR.')

@php
    $total    = $notificaciones->total();
    $todasIds  = $notificaciones->getCollection()->map(fn($n) => $n->id)->all();
    $leidasIds = $notificaciones->getCollection()
        ->filter(fn($n) => !is_null($n->read_at))
        ->map(fn($n) => $n->id)
        ->all();

    $tipos = [
        'nuevo_trabajo'      => ['label' => 'Nuevo trabajo',      'bg' => 'bg-blue-100',     'text' => 'text-blue-600',     'icon' => 'M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'asignacion'         => ['label' => 'Asignación',         'bg' => 'bg-purple-100',   'text' => 'text-purple-600',   'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        'plazo'              => ['label' => 'Plazo',              'bg' => 'bg-amber-100',    'text' => 'text-amber-600',    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        'retroalimentacion'  => ['label' => 'Retroalimentación',  'bg' => 'bg-indigo-100',   'text' => 'text-indigo-600',   'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
        'nueva_version'      => ['label' => 'Documento corregido', 'bg' => 'bg-teal-100',     'text' => 'text-teal-600',     'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12'],
        'aprobado'           => ['label' => 'Aprobado',           'bg' => 'bg-green-100',    'text' => 'text-green-600',    'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
        'propuesta_evaluada' => ['label' => 'Propuesta evaluada', 'bg' => 'bg-emerald-100',  'text' => 'text-emerald-600',  'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'trabajo_retirado'   => ['label' => 'Trabajo retirado',   'bg' => 'bg-orange-100',   'text' => 'text-orange-600',   'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'trabajo_reactivado' => ['label' => 'Trabajo reactivado', 'bg' => 'bg-emerald-100',  'text' => 'text-emerald-600',  'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
        'trabajo_eliminado'  => ['label' => 'Trabajo eliminado',  'bg' => 'bg-red-100',      'text' => 'text-red-600',      'icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'],
        'trabajo_aceptado'   => ['label' => 'Trabajo aceptado',   'bg' => 'bg-green-100',    'text' => 'text-green-600',    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'trabajo_rechazado'  => ['label' => 'Trabajo rechazado',  'bg' => 'bg-red-100',      'text' => 'text-red-600',      'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'acta_sustentacion'  => ['label' => 'Acta de sustentación', 'bg' => 'bg-teal-100',   'text' => 'text-teal-600',     'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'info'               => ['label' => 'Información',        'bg' => 'bg-gray-100',     'text' => 'text-gray-600',     'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'success'            => ['label' => 'Completado',         'bg' => 'bg-green-100',    'text' => 'text-green-600',    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'warning'            => ['label' => 'Advertencia',        'bg' => 'bg-amber-100',    'text' => 'text-amber-600',    'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z'],
        'error'              => ['label' => 'Error',              'bg' => 'bg-red-100',      'text' => 'text-red-600',      'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];
@endphp

@section('content')
<x-notification type="success" />
<x-notification type="error" />

<div x-data="panelNotificaciones()"
     x-init="init()"
     class="max-w-3xl mx-auto">

    {{-- Encabezado --}}
    <div class="mb-6">
        <div class="mb-4">
            <a href="{{ $backUrl }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-50 hover:text-[#07321e] transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver
            </a>
        </div>
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Notificaciones</h1>
                <p class="text-sm text-gray-500 mt-1">
                    <span x-text="total === 1 ? '1 notificación' : total + ' notificaciones'"></span>
                    <template x-if="noLeidas > 0">
                        <span>
                            · <span class="font-semibold text-[var(--cecar-green)]" x-text="noLeidas + ' sin leer'"></span>
                        </span>
                    </template>
                </p>
            </div>
            <div class="flex items-center gap-2" x-show="total > 0">
                <button type="button" @click="marcarTodas" x-show="noLeidas > 0"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Marcar todas como leídas
                </button>
                <button type="button" @click="showDeleteModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Borrar todas
                </button>
            </div>
        </div>
    </div>

    {{-- Lista --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden" x-show="total > 0">
        @forelse ($notificaciones as $notif)
            @php
                $titulo  = $notif->data['titulo'] ?? $notif->data['title'] ?? 'Notificación';
                $mensaje = $notif->data['mensaje'] ?? $notif->data['message'] ?? '';
                $noLeida = is_null($notif->read_at);
                $tipoInfo = $tipos[$notif->data['tipo'] ?? 'info'] ?? $tipos['info'];
            @endphp

            <div class="px-5 py-4 border-b border-gray-100 last:border-b-0 border-l-2 border-l-transparent flex gap-4"
                 :class="leidas.includes('{{ $notif->id }}') ? '' : 'bg-gray-50 border-l-[var(--cecar-green)]'">

                <div class="flex-shrink-0 w-11 h-11 rounded-lg flex items-center justify-center {{ $tipoInfo['bg'] }}">
                    <svg class="w-5 h-5 {{ $tipoInfo['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tipoInfo['icon'] }}"/>
                    </svg>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-3">
                        <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded {{ $tipoInfo['bg'] }} {{ $tipoInfo['text'] }}">
                            {{ $tipoInfo['label'] }}
                        </span>
                        <span class="text-xs text-gray-600 whitespace-nowrap" title="{{ $notif->created_at->format('d/m/Y h:i A') }}">
                            {{ $notif->created_at->diffForHumans() }}
                        </span>
                    </div>

                    <h2 class="text-sm leading-snug mt-1.5"
                        :class="leidas.includes('{{ $notif->id }}') ? 'font-medium text-gray-600' : 'font-semibold text-gray-900'">
                        {{ $titulo }}
                    </h2>

                    <p class="text-sm text-gray-600 mt-1 leading-relaxed whitespace-pre-line">{{ $mensaje }}</p>

                    <div class="mt-2.5">
                        <button type="button" @click="marcarLeida('{{ $notif->id }}')"
                                x-show="!leidas.includes('{{ $notif->id }}')"
                                class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-[var(--cecar-green)] transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Marcar como leída
                        </button>
                    </div>
                </div>
            </div>
        @empty
        @endforelse
    </div>

    {{-- Estado vacío --}}
    <div class="bg-white border border-gray-200 rounded-xl px-6 py-16 text-center" x-show="total === 0">
        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
        </div>
        <h3 class="text-base font-semibold text-gray-700">No tienes notificaciones</h3>
        <p class="text-sm text-gray-600 mt-1">Aquí aparecerán las alertas y novedades del sistema.</p>
    </div>

    {{-- Paginación --}}
    @if($notificaciones->hasPages())
        <div class="mt-6" x-show="total > 0">
            {{ $notificaciones->links('pagination::tailwind') }}
        </div>
    @endif

    {{-- Modal: Borrar todas --}}
    <div x-show="showDeleteModal" x-cloak
         @keydown.escape.window="showDeleteModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showDeleteModal = false"></div>
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-8 text-center z-10 border border-gray-100">
            <div class="w-16 h-16 rounded-full bg-rose-50 flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">¿Borrar todas las notificaciones?</h3>
            <p class="text-sm text-gray-500 mb-6">Se eliminarán permanentemente todas tus notificaciones. Esta acción no se puede deshacer.</p>
            <div class="flex flex-col sm:flex-row-reverse gap-3">
                <button @click="borrarTodas()" :disabled="deleting"
                        class="w-full px-8 py-3 rounded-xl bg-rose-600 text-white font-bold hover:bg-rose-700 transition-all flex items-center justify-center gap-2 shadow-lg shadow-rose-200 disabled:opacity-60">
                    <span x-show="!deleting">Borrar todas</span>
                    <svg x-show="deleting" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
                <button type="button" @click="showDeleteModal = false"
                        class="w-full px-8 py-3 rounded-xl bg-white text-gray-500 font-bold border border-gray-200 hover:text-gray-700 hover:bg-gray-100 transition-all">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('panelNotificaciones', () => ({
        total: {{ $total }},
        noLeidas: {{ $noLeidas }},
        leidas: @json($leidasIds),
        todasIds: @json($todasIds),
        showDeleteModal: false,
        deleting: false,

        init() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = meta.getAttribute('content');
            }
        },

        async marcarLeida(id) {
            if (this.leidas.includes(id)) return;
            try {
                await window.axios.post(`/notificaciones/${id}/leida`);
                this.leidas.push(id);
                this.noLeidas = Math.max(0, this.noLeidas - 1);
            } catch (e) {
                console.error('Error al marcar notificación como leída:', e);
            }
        },

        async marcarTodas() {
            if (this.noLeidas === 0) return;
            try {
                await window.axios.post('/notificaciones/todas-leidas');
                this.leidas = this.todasIds.slice();
                this.noLeidas = 0;
            } catch (e) {
                console.error('Error al marcar todas como leídas:', e);
            }
        },

        async borrarTodas() {
            if (this.deleting) return;
            this.deleting = true;
            try {
                await window.axios.delete('/notificaciones/todas');
                this.total = 0;
                this.noLeidas = 0;
                this.showDeleteModal = false;
            } catch (e) {
                console.error('Error al borrar notificaciones:', e);
            } finally {
                this.deleting = false;
            }
        },
    }));
});
</script>
@endpush
