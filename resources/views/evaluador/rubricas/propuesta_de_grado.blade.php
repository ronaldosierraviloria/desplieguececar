{{-- Formato FO-TG-006: Evaluación de la Propuesta de Trabajo de Grado --}}

<div class="bg-white text-black p-5 md:p-6 border border-gray-300 shadow-md rounded-xl max-w-4xl mx-auto space-y-5 font-sans">
    
    {{-- CABECERA OFICIAL FO-TG-006 --}}
    <div class="border-2 border-black">
        <div class="grid grid-cols-12 items-center text-center divide-x-2 divide-black">
            <div class="col-span-3 p-3 flex justify-center items-center">
                <img src="{{ asset('images/logocecar.webp') }}" alt="Logo CECAR" class="h-12 w-auto object-contain">
            </div>
            <div class="col-span-6 p-3 flex flex-col justify-center text-center font-bold text-xs leading-snug uppercase">
                <span>EVALUACIÓN DE LA</span>
                <span>PROPUESTA DE TRABAJO</span>
                <span>GRADO</span>
            </div>
            <div class="col-span-3 p-3 flex flex-col justify-center text-center font-bold text-xs leading-relaxed">
                <span>FCBIA</span>
                <span>FO-TG-006</span>
            </div>
        </div>
    </div>

    {{-- INFORMACIÓN BÁSICA DEL ANTEPROYECTO --}}
    <div class="border-2 border-black overflow-hidden">
        <table class="w-full border-collapse text-xs">
            <tr class="border-b border-black">
                <td class="w-1/4 p-2.5 font-bold border-r border-black bg-gray-50">Título de la Propuesta:</td>
                <td class="p-2.5 font-bold text-gray-900">{{ $trabajo->titulo }}</td>
            </tr>
            <tr class="border-b border-black">
                <td class="p-2.5 font-bold border-r border-black bg-gray-50">Presentado por:</td>
                <td class="p-2.5 font-semibold text-gray-900">
                    @if($trabajo->estudiante && $trabajo->estudiante->count() > 0)
                        @foreach($trabajo->estudiante as $est)
                            {{ $est->nombre }} {{ $est->apellido }}{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    @else
                        <span class="text-gray-600 italic">No asignado</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="p-2.5 font-bold border-r border-black bg-gray-50">Director (es):</td>
                <td class="p-2.5 font-semibold text-gray-900">
                    @if($trabajo->directores && $trabajo->directores->count() > 0)
                        @foreach($trabajo->directores as $dir)
                            {{ $dir->nombre }} {{ $dir->apellido }}{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    @else
                        <span class="text-gray-600 italic">No asignado</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- EVALUACIÓN CUANTITATIVA --}}
    <div class="border-2 border-black overflow-hidden">
        <table class="w-full border-collapse text-xs">
            <thead>
                <tr class="border-b-2 border-black bg-gray-100 font-bold uppercase text-center">
                    <th class="p-2 text-left border-r border-black">EVALUACIÓN CUANTITATIVA</th>
                    <th class="p-2 w-16 border-r border-black text-center">%</th>
                    <th class="p-2 w-24 text-center">0 - 5</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black">
                @php
                    $criteriosPropuesta = [
                        1 => ['desc' => 'El título está acorde con el problema a resolver.', 'pct' => 5],
                        2 => ['desc' => 'La formulación y justificación del problema responden al trabajo planteado.', 'pct' => 20],
                        3 => ['desc' => 'El cumplimiento del objetivo general garantiza la solución al problema planteado.', 'pct' => 20],
                        4 => ['desc' => 'El cumplimiento de los objetivos específicos asegura el logro del objetivo general y están acordes para un trabajo de pregrado.', 'pct' => 20],
                        5 => ['desc' => 'El marco referencial presentado da respuesta al problema planteado.', 'pct' => 10],
                        6 => ['desc' => 'La metodología planteada reporta antecedentes claves relacionados con el objeto de estudio y con la estrategia propuesta, permitiendo así el cumplimiento de los objetivos.', 'pct' => 20],
                        7 => ['desc' => 'El tiempo estimado para el desarrollo de las actividades (cronograma), es conforme con el alcance planteado y las referencias bibliográficas son actualizadas y se relacionan con el tema de la investigación.', 'pct' => 5],
                    ];
                    $esMismaPlantilla = ($evaluacionPrevia?->tipo_plantilla === 'propuesta_de_grado');
                @endphp

                @foreach($criteriosPropuesta as $idx => $crit)
                @php
                    $prevCal = $esMismaPlantilla ? ((collect($evaluacionPrevia?->criterios ?? [])->firstWhere('id', $idx))['calificacion'] ?? '') : '';
                    $prevObs = $esMismaPlantilla ? ((collect($evaluacionPrevia?->criterios ?? [])->firstWhere('id', $idx))['comentario'] ?? '') : '';
                @endphp
                <tr>
                    <td class="p-3 border-r border-black">
                        <p class="font-medium text-gray-900 leading-relaxed">{{ $crit['desc'] }}</p>
                    </td>
                    <td class="p-2 border-r border-black text-center font-bold text-sm bg-gray-50">
                        {{ $crit['pct'] }}
                    </td>
                    <td class="p-2 text-center align-middle">
                        <input type="number" id="nota_propuesta_{{ $idx }}" min="0" max="5" step="0.1" placeholder="0.0"
                            oninput="calcularNotaPropuesta()" value="{{ $prevCal }}"
                            class="w-16 border-2 border-black rounded px-2 py-1 text-sm text-center font-bold focus:bg-amber-50 outline-none">
                    </td>
                </tr>
                @endforeach

                <tr class="bg-gray-100 font-bold border-t-2 border-black">
                    <td class="p-3 border-r border-black uppercase text-xs">
                        EL EVALUADOR APRUEBA EL ANTEPROYECTO (SI CUMPLE CON UN MÍNIMO DE 3.0)
                    </td>
                    <td class="p-2 border-r border-black text-center text-sm">100%</td>
                    <td class="p-2 text-center text-base text-[#07321e]">
                        <input type="text" id="nota-final" readonly placeholder="0.0" class="w-16 bg-transparent text-center font-black text-base outline-none">
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- OPCIONES DE RESULTADO / APROBACIÓN DE LA PROPUESTA --}}
    <div class="space-y-2 text-xs font-bold pt-1">
        <div id="propuesta_aceptada" class="flex items-center justify-between p-2.5 rounded-lg border border-gray-300 bg-gray-50 transition-all">
            <span class="flex items-center gap-2">
                <span class="w-4 h-4 border border-black inline-flex items-center justify-center text-xs">✓</span>
                Aceptada
            </span>
            <span class="check-indicator text-emerald-700 font-bold hidden">✔ Seleccionada (4.2 - 5.0)</span>
            <span class="text-gray-500 font-semibold">(4.2 - 5.0)</span>
        </div>
        <div id="propuesta_mejoras" class="flex items-center justify-between p-2.5 rounded-lg border border-gray-300 bg-gray-50 transition-all">
            <span class="flex items-center gap-2">
                <span class="w-4 h-4 border border-black inline-flex items-center justify-center text-xs">✓</span>
                Aceptada con modificaciones mayores
            </span>
            <span class="check-indicator text-amber-700 font-bold hidden">✔ Seleccionada (3.0 – 4.19)</span>
            <span class="text-gray-500 font-semibold">(3.0 – 4.19)</span>
        </div>
        <div id="propuesta_rechazada" class="flex items-center justify-between p-2.5 rounded-lg border border-gray-300 bg-gray-50 transition-all">
            <span class="flex items-center gap-2">
                <span class="w-4 h-4 border border-black inline-flex items-center justify-center text-xs">✓</span>
                Rechazada
            </span>
            <span class="check-indicator text-red-700 font-bold hidden">✔ Seleccionada (&lt;3.0)</span>
            <span class="text-gray-500 font-semibold">(&lt;3.0)</span>
        </div>
    </div>

    {{-- COMENTARIOS Y OBSERVACIONES ADICIONALES --}}
    <div class="mt-5 border-2 border-black overflow-hidden">
        <div class="bg-gray-100 p-2 border-b-2 border-black font-bold uppercase text-xs text-center">
            COMENTARIOS Y OBSERVACIONES ADICIONALES (si es necesario por favor emplee una hoja adicional)
        </div>
        <div class="p-0">
            <textarea id="observacion_final" rows="5" oninput="autoResizeCaja(this)"
                class="w-full min-h-[120px] p-3 text-xs font-medium text-gray-900 border-none outline-none resize-none bg-transparent"
                placeholder="Escriba aquí los comentarios adicionales...">{{ ($evaluacionPrevia->observaciones_por_tipo ?? [])['propuesta_de_grado'] ?? ($esMismaPlantilla ? ($evaluacionPrevia->observaciones_globales ?? '') : '') }}</textarea>
        </div>
    </div>
</div>

<script>
    function autoResizeCaja(element) {
        if (!element) return;
        element.style.height = 'auto';
        element.style.height = Math.max(120, element.scrollHeight) + 'px';
    }

    const pesos = {
        1: 0.05,
        2: 0.20,
        3: 0.20,
        4: 0.20,
        5: 0.10,
        6: 0.20,
        7: 0.05
    };

    function calcularNotaPropuesta() {
        let notaPonderada = 0;
        let todosCalificados = true;

        for (let i = 1; i <= 7; i++) {
            const inputVal = document.getElementById('nota_propuesta_' + i)?.value ?? '';
            const nota = parseFloat(inputVal);

            if (isNaN(nota) || inputVal === '') {
                todosCalificados = false;
                continue;
            }

            let validNota = Math.max(0, Math.min(5, nota));
            notaPonderada += (validNota * pesos[i]);
        }

        const notaFinalInput = document.getElementById('nota-final');
        if (notaFinalInput) {
            notaFinalInput.value = notaPonderada > 0 ? notaPonderada.toFixed(2) : '';
        }

        const divAceptada = document.getElementById('propuesta_aceptada');
        const divMejoras = document.getElementById('propuesta_mejoras');
        const divRechazada = document.getElementById('propuesta_rechazada');

        if (divAceptada && divMejoras && divRechazada) {
            [divAceptada, divMejoras, divRechazada].forEach(d => {
                d.className = "flex items-center justify-between p-2.5 rounded-lg border border-gray-300 bg-gray-50 text-gray-700 font-semibold";
                const chk = d.querySelector('.check-indicator');
                if (chk) chk.classList.add('hidden');
            });

            if (todosCalificados || notaPonderada > 0) {
                if (notaPonderada >= 4.2) {
                    divAceptada.className = "flex items-center justify-between p-2.5 rounded-lg border-2 border-emerald-600 bg-emerald-50 text-emerald-900 font-bold";
                    divAceptada.querySelector('.check-indicator')?.classList.remove('hidden');
                } else if (notaPonderada >= 3.0) {
                    divMejoras.className = "flex items-center justify-between p-2.5 rounded-lg border-2 border-amber-600 bg-amber-50 text-amber-900 font-bold";
                    divMejoras.querySelector('.check-indicator')?.classList.remove('hidden');
                } else {
                    divRechazada.className = "flex items-center justify-between p-2.5 rounded-lg border-2 border-red-600 bg-red-50 text-red-900 font-bold";
                    divRechazada.querySelector('.check-indicator')?.classList.remove('hidden');
                }
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const obsFinal = document.getElementById('observacion_final');
        if (obsFinal) autoResizeCaja(obsFinal);
        setTimeout(calcularNotaPropuesta, 300);
    });
</script>

