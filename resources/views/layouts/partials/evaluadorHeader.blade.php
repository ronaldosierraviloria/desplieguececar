<header class="sticky top-0 z-30 flex items-center justify-between px-6 py-4 bg-white/80 backdrop-blur-md border-b border-gray-100 shadow-sm">
    <div class="flex items-center gap-4">
        {{-- Botón Hamburguesa --}}
        <button onclick="window.__toggleSidebar && window.__toggleSidebar()" class="p-2 text-gray-500 rounded-lg hover:bg-gray-100 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        
        <h2 class="text-xl font-bold text-gray-800 tracking-tight">
            @yield('title', 'Panel Evaluador')
        </h2>
    </div>

    <div class="flex items-center gap-3">
        <!-- Recorrido guiado -->
        <button type="button" onclick="window.restartTour && window.restartTour()"
            title="Ver recorrido guiado"
            class="p-2 text-gray-500 hover:text-[var(--cecar-green)] transition-colors rounded-lg hover:bg-gray-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </button>

        <!-- Notificaciones -->
        <x-notificaciones-dropdown />

        <!-- Perfil - Flowbite Dropdown -->
        <button id="dropdownUserButton" data-dropdown-toggle="dropdownUser" data-dropdown-placement="bottom-end"
            class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-100 transition-colors">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center font-bold shadow-sm"
                style="background-color: var(--cecar-lime); color: var(--cecar-green);">
                {{ substr(Auth::user()->nombre ?? 'E', 0, 1) }}{{ substr(Auth::user()->apellido ?? '', 0, 1) }}
            </div>
            <span class="hidden sm:block text-sm font-semibold text-gray-700">{{ Auth::user()->nombre ?? 'Evaluador' }}</span>
            <svg class="hidden sm:block w-3.5 h-3.5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
        </button>

        <!-- Dropdown menu -->
        <div id="dropdownUser" class="z-50 hidden my-2 text-base list-none bg-white divide-y divide-gray-100 rounded-2xl shadow-2xl border border-gray-100">
            <div class="px-4 py-3">
                <span class="block text-sm font-bold text-gray-800">{{ Auth::user()->nombre ?? 'Evaluador' }} {{ Auth::user()->apellido ?? '' }}</span>
                <span class="block text-xs text-gray-500 truncate">{{ Auth::user()->rol ?? 'Evaluador' }}</span>
            </div>
            <ul class="py-2">
                <li>
                    <a href="{{ route('user.perfil') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-[var(--cecar-lime)] hover:text-[var(--cecar-green)] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Mi Perfil
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
