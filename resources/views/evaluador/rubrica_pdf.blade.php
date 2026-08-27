<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @php
        $trabajo = $evaluacion->trabajo;
        $criterios = $evaluacion->criterios ?? [];
        $tipo = $evaluacion->tipo_plantilla;
        $isPropuesta = $tipo === 'propuesta_de_grado';
        $isPasantia = $tipo === 'pasantia';
        $formatoCodigo = $isPropuesta ? 'FO-TG-006' : ($isPasantia ? 'FO-TG-008' : 'FO-TG-010');

        $resultadoRaw = $evaluacion->resultado ?? '';
        $notaFinalNum = $evaluacion->nota_final;

        // Opción seleccionada según el tipo de plantilla (mismas opciones que la vista de evaluación)
        // Propuesta: Aceptada / Aceptada con modificaciones mayores / Rechazada (según nota ponderada)
        $isAceptada  = ($resultadoRaw === 'aceptada') || ($notaFinalNum !== null && $notaFinalNum >= 4.2);
        $isMejoras   = ($resultadoRaw === 'aceptada_con_mejoras') || ($notaFinalNum !== null && $notaFinalNum >= 3.0 && $notaFinalNum < 4.2);
        $isRechazada = ($resultadoRaw === 'rechazada') || ($notaFinalNum !== null && $notaFinalNum < 3.0 && $notaFinalNum > 0);
        // Trabajo de grado / Pasantía: opciones basadas en el resultado textual
        $isPuedeSustentar  = $resultadoRaw === 'puede_sustentar';
        $isConCorrecciones = $resultadoRaw === 'sustentacion_con_correcciones';
        $isReestructurar   = $resultadoRaw === 'no_sustentar';

        $fechaEvaluacion = $evaluacion->updated_at ? \Carbon\Carbon::parse($evaluacion->updated_at)->format('d/m/Y') : date('d/m/Y');

        $codigoProyectoClean = $evaluacion->trabajo->codigo_proyecto ?? ('TRABAJO_' . $evaluacion->trabajo->id_trabajo);
        $nombreRubricaDoc = $isPropuesta 
            ? 'EVALUACIÓN DE LA PROPUESTA DE TRABAJO GRADO' 
            : ($isPasantia ? 'FORMATO DE EVALUACIÓN DE PASANTÍA' : 'EVALUACIÓN DE INFORME FINAL DE TRABAJO GRADO');

        $nombreArchivoPDF = "{$codigoProyectoClean}-{$nombreRubricaDoc} - FCBIA {$formatoCodigo}.pdf";
    @endphp
    <title>{{ $nombreArchivoPDF }}</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
            color: #000;
        }
        #contenido-rubrica {
            width: 750px;
            background: #ffffff;
            padding: 25px 30px 20px 30px;
            box-sizing: border-box;
            font-size: 11px;
            color: #000;
            position: relative;
        }

        /* CABECERA OFICIAL CECAR */
        .header-table {
            width: 100%;
            border: 2px solid #000000;
            border-collapse: collapse;
            margin-bottom: 16px;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .header-table td {
            padding: 8px 10px;
            vertical-align: middle;
            text-align: center;
            border: 1px solid #000000;
        }
        .col-logo {
            width: 25%;
        }
        .col-title {
            width: 53%;
            font-weight: bold;
            font-size: 12px;
            line-height: 1.3;
            text-transform: uppercase;
        }
        .col-code {
            width: 22%;
            font-weight: bold;
            font-size: 11px;
            line-height: 1.4;
        }
        .logo-img {
            max-height: 52px;
            max-width: 150px;
            object-fit: contain;
        }

        /* INFORMACIÓN DEL PROYECTO */
        .info-table {
            width: 100%;
            border: 2px solid #000000;
            border-collapse: collapse;
            margin-bottom: 16px;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .info-table td {
            padding: 7px 10px;
            border: 1px solid #000000;
            font-size: 11px;
            vertical-align: middle;
        }
        .info-label {
            width: 26%;
            font-weight: bold;
            color: #000000;
        }
        .info-value {
            font-weight: bold;
            color: #000000;
        }

        /* EVALUACIÓN CUANTITATIVA TABLE */
        .eval-table {
            width: 100%;
            border: 2px solid #000000;
            border-collapse: collapse;
            margin-bottom: 14px;
            table-layout: fixed;
        }
        .eval-table th, .eval-table td {
            border: 1px solid #000000;
            padding: 8px 10px;
            font-size: 11px;
            vertical-align: top !important;
            text-align: left !important;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .eval-table tr {
            /* Mantener cada fila íntegra: si no cabe en la página, se empuja completa a la siguiente */
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .eval-table th {
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            background: #ffffff;
        }
        .eval-desc-header {
            text-align: center !important;
            font-size: 11px;
        }
        .eval-pct-header {
            width: 7%;
            text-align: center !important;
            font-weight: bold;
        }
        .eval-nota-header {
            width: 9%;
            text-align: center !important;
            font-weight: bold;
        }
        .crit-text {
            line-height: 1.3;
        }
        .crit-pct {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }
        .crit-nota {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }
        .crit-comentario {
            text-align: left;
            font-weight: normal;
            font-size: 10px;
            line-height: 1.35;
        }
        .eval-summary-row td {
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            padding: 8px;
            text-transform: uppercase;
            background: #f8fafc;
        }

        /* SECCIÓN DE APROBACIÓN */
        .aprobacion-box {
            margin-bottom: 16px;
            padding: 4px 0;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .aprobacion-item {
            display: flex;
            align-items: baseline;
            margin-bottom: 5px;
            font-size: 11.5px;
            font-weight: bold;
        }
        .aprobacion-name {
            width: 320px;
            white-space: normal;
        }
        .aprobacion-line {
            flex: 1;
            border-bottom: 1.5px solid #000000;
            margin: 0 10px;
            position: relative;
            height: 14px;
        }
        .aprobacion-selected-text {
            position: absolute;
            bottom: 1px;
            left: 10px;
            font-weight: bold;
            font-size: 11px;
            color: #000000;
        }
        .aprobacion-range {
            width: 80px;
            text-align: right;
            font-weight: bold;
        }
        .check-box-icon {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1.5px solid #000;
            margin-right: 6px;
            vertical-align: middle;
            text-align: center;
            line-height: 11px;
            font-size: 11px;
        }

        /* COMENTARIOS Y OBSERVACIONES ADICIONALES (ADAPTABLE) */
        .comments-section {
            margin-bottom: 20px;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .comments-header-label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 6px;
            color: #000000;
        }
        .comments-border-box {
            border: 1.5px solid #000000;
            width: 100%;
            min-height: 130px;
            padding: 10px 12px;
            box-sizing: border-box;
            font-size: 10.5px;
            line-height: 1.45;
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow: visible;
        }

        /* SECCIÓN DE FIRMAS */
        .signatures-section {
            margin-top: 24px;
            margin-bottom: 20px;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .signatures-grid {
            display: flex;
            justify-content: space-between;
            gap: 40px;
        }
        .signature-col {
            flex: 1;
            text-align: center;
        }
        .signature-line-container {
            height: 60px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 4px;
        }
        .signature-image {
            max-height: 52px;
            max-width: 180px;
            object-fit: contain;
            margin-bottom: 2px;
        }
        .signature-line {
            width: 100%;
            border-top: 1.5px solid #000000;
        }
        .signature-label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .signature-name-sub {
            font-size: 9.5px;
            font-weight: bold;
            color: #333333;
            margin-top: 2px;
        }

        /* PIE DE PÁGINA OFICIAL CECAR */
        .official-footer {
            border-top: 1px solid #666666;
            padding-top: 6px;
            margin-top: 25px;
            text-align: center;
            font-size: 8px;
            color: #444444;
            line-height: 1.35;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        @media print {
            body { padding: 0; background: #fff; }
            .no-print { display: none !important; }
            #contenido-rubrica { width: 100%; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; padding: 15px; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; position: sticky; top: 0; z-index: 50;">
        <button onclick="generarPDF()" style="padding:10px 24px; background:#000000; color:#ffffff; border:none; border-radius:4px; font-weight:bold; cursor:pointer; font-size:13px; transition: background 0.2s;">
            Descargar PDF
        </button>
    </div>

    <div id="contenido-rubrica">
        {{-- CABECERA OFICIAL CECAR --}}
        <table class="header-table">
            <tr>
                <td class="col-logo">
                    <img src="{{ asset('images/logocecar.webp') }}" alt="Logo CECAR" class="logo-img" onerror="this.style.display='none'">
                </td>
                <td class="col-title">
                    @if($isPropuesta)
                        EVALUACIÓN DE LA<br>PROPUESTA DE TRABAJO<br>GRADO
                    @elseif($isPasantia)
                        FORMATO DE EVALUACIÓN<br>DE PASANTÍA
                    @else
                        EVALUACIÓN DE INFORME<br>FINAL DE TRABAJO GRADO
                    @endif
                </td>
                <td class="col-code">
                    FCBIA<br>
                    {{ $isPropuesta ? 'FO-TG-006' : ($isPasantia ? 'FO-TG-008' : 'FO-TG-010') }}
                </td>
            </tr>
        </table>

        {{-- INFORMACIÓN DEL PROYECTO --}}
        <table class="info-table">
            <tr>
                <td class="info-label">Título de la Propuesta:</td>
                <td class="info-value">{{ $trabajo->titulo }}</td>
            </tr>
            <tr>
                <td class="info-label">Presentado por:</td>
                <td class="info-value">
                    @if($trabajo->estudiante && $trabajo->estudiante->count() > 0)
                        @foreach($trabajo->estudiante as $est)
                            {{ $est->nombre }} {{ $est->apellido }}@if(!$loop->last), @endif
                        @endforeach
                    @else
                        No asignado
                    @endif
                </td>
            </tr>
            <tr>
                <td class="info-label">Director (es):</td>
                <td class="info-value">
                    @if($trabajo->directores && $trabajo->directores->count() > 0)
                        @foreach($trabajo->directores as $dir)
                            {{ $dir->nombre }} {{ $dir->apellido }}@if(!$loop->last), @endif
                        @endforeach
                    @else
                        No asignado
                    @endif
                </td>
            </tr>
        </table>

        {{-- EVALUACIÓN CUANTITATIVA --}}
        @if($isPropuesta)
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
            @endphp

            <table class="eval-table">
                <thead>
                    <tr>
                        <th class="eval-desc-header">EVALUACIÓN CUANTITATIVA</th>
                        <th class="eval-pct-header">%</th>
                        <th class="eval-nota-header">0-5</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($criteriosPropuesta as $idx => $crit)
                        @php $criterioData = $criterios[$idx - 1] ?? []; @endphp
                        <tr>
                            <td class="crit-text">{{ $crit['desc'] }}</td>
                            <td class="crit-pct">{{ $crit['pct'] }}</td>
                            <td class="crit-nota">
                                {{ isset($criterioData['calificacion']) ? number_format($criterioData['calificacion'], 1) : '' }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="eval-summary-row">
                        <td style="text-align: left; font-weight: bold;">
                            EL EVALUADOR APRUEBA EL ANTEPROYECTO (SI CUMPLE CON UN MÍNIMO DE 3.0)
                        </td>
                        <td class="crit-pct">100%</td>
                        <td class="crit-nota" style="font-size: 14px;">
                            {{ $notaFinalNum !== null ? number_format($notaFinalNum, 1) : '' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        @else
            @php
                $preguntas = $tipo === 'pasantia' ? [
                    1 => 'Desempeño general durante la pasantía: Cumplimiento de horarios, responsabilidad, iniciativa y actitud profesional.',
                    2 => 'Aplicación de conocimientos académicos: El pasante demuestra capacidad para aplicar los conocimientos adquiridos en el programa académico al contexto laboral.',
                    3 => 'Calidad del informe final: El informe presenta estructura adecuada, coherencia, análisis reflexivo y cumplimiento del formato establecido.',
                    4 => 'Relación con el proyecto de grado: La pasantía está relacionada con la línea de investigación o el tema del trabajo de grado.',
                    5 => 'Impacto y resultados obtenidos: La pasantía generó aportes significativos a la empresa/institución y al desarrollo profesional del pasante.',
                    6 => 'Satisfacción de la empresa/organización: La entidad receptora manifiesta conformidad con el desempeño y resultados del pasante.',
                ] : [
                    1 => 'El título. ¿Está el título acorde con las expectativas planteadas en la investigación?',
                    2 => 'Introducción (incluye planteamiento del problema y justificación). ¿Estuvo bien definido el problema investigado?, ¿fue clara su justificación desde el punto de vista académico, científico, tecnológico, social o económico?',
                    3 => 'Marco referencial. ¿La revisión bibliográfica es apropiada, completa y coherente?, ¿El marco referencial está actualizado y acertado con respecto al problema que se estudió?, ¿Es consistente el uso del formato de citas y referencias bibliográficas?',
                    4 => 'Cumplimiento de objetivos. ¿Se evidencia el cumplimiento de los objetivos en los resultados obtenidos?',
                    5 => 'Evaluación de la metodología. ¿sigue métodos reconocidos internacionalmente?, ¿está la metodología claramente descrita?, ¿esta relacionada con los objetivos planteados?, ¿está validada y respaldada con referencias bibliográficas?, ¿el tratamiento estadístico de los datos fue claro y adecuado metodológicamente (si aplica)?',
                    6 => 'Novedad y pertinencia de los resultados. ¿Los resultados obtenidos son claros y consistentes con los objetivos del proyecto?',
                    7 => 'Conclusiones. ¿Las conclusiones están acordes a los resultados obtenidos?',
                ];
            @endphp

            <table class="eval-table">
                <thead>
                    <tr>
                        <th class="eval-desc-header" style="width: 100%; text-align: left; padding: 7px 10px; background: #f8fafc; font-size: 11px;">
                            CRITERIOS DE EVALUACIÓN Y ANÁLISIS DETALLADO
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preguntas as $idx => $pregunta)
                        @php $criterioData = $criterios[$idx - 1] ?? []; @endphp
                        <tr style="page-break-inside: avoid; break-inside: avoid;">
                            <td style="width: 100%; padding: 10px 12px; border: 1.5px solid #000000; background: #ffffff; text-align: left !important; vertical-align: top !important;">
                                {{-- PREGUNTA ARRIBA (100% ANCHO, ALINEADA A START/IZQUIERDA) --}}
                                <div style="font-weight: bold; font-size: 11px; color: #000000; line-height: 1.4; margin-bottom: 6px; text-align: left !important;">
                                    {{ $idx }}. {{ $pregunta }}
                                </div>
                                
                                {{-- RESPUESTA / COMENTARIO ABAJO (100% ANCHO, ALINEADO A START/IZQUIERDA) --}}
                                @if($isPasantia)
                                    <div style="font-size: 10.5px; color: #1e293b; margin-top: 4px; text-align: left !important; width: 100%;">
                                        <strong>Valoración:</strong> <span style="color: #000000;">{{ !empty($criterioData['valoracion']) ? ucfirst($criterioData['valoracion']) : 'Sin valoración' }}</span>
                                        @if(!empty($criterioData['comentario']))
                                            <div style="color: #334155; margin-top: 4px; white-space: pre-wrap; word-wrap: break-word; text-align: left !important; width: 100%;">{{ $criterioData['comentario'] }}</div>
                                        @endif
                                    </div>
                                @else
                                    @if(!empty($criterioData['comentario']))
                                        <div style="font-size: 10.5px; color: #334155; margin-top: 4px; white-space: pre-wrap; word-wrap: break-word; line-height: 1.45; text-align: left !important; width: 100%;">{{ trim($criterioData['comentario']) }}</div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- OPCIONES DE RESULTADO / APROBACIÓN SEGÚN EL TIPO DE PLANTILLA --}}
        @if($isPropuesta)
        <div class="aprobacion-box">
            <div class="aprobacion-item">
                <span class="aprobacion-name">
                    <span class="check-box-icon">{{ $isAceptada ? '✓' : '' }}</span>
                    Aceptada
                </span>
                <div class="aprobacion-line">
                    @if($isAceptada)
                        <span class="aprobacion-selected-text">
                            SELECCIONADO @if($notaFinalNum !== null)(Nota Final: {{ number_format($notaFinalNum, 1) }})@endif
                        </span>
                    @endif
                </div>
                <span class="aprobacion-range">(4.2 - 5.0)</span>
            </div>

            <div class="aprobacion-item">
                <span class="aprobacion-name">
                    <span class="check-box-icon">{{ $isMejoras ? '✓' : '' }}</span>
                    Aceptada con modificaciones mayores
                </span>
                <div class="aprobacion-line">
                    @if($isMejoras)
                        <span class="aprobacion-selected-text">
                            SELECCIONADO @if($notaFinalNum !== null)(Nota Final: {{ number_format($notaFinalNum, 1) }})@endif
                        </span>
                    @endif
                </div>
                <span class="aprobacion-range">(3.0 – 4.19)</span>
            </div>

            <div class="aprobacion-item">
                <span class="aprobacion-name">
                    <span class="check-box-icon">{{ $isRechazada ? '✓' : '' }}</span>
                    Rechazada
                </span>
                <div class="aprobacion-line">
                    @if($isRechazada)
                        <span class="aprobacion-selected-text">
                            SELECCIONADO @if($notaFinalNum !== null)(Nota Final: {{ number_format($notaFinalNum, 1) }})@endif
                        </span>
                    @endif
                </div>
                <span class="aprobacion-range">(&lt;3.0)</span>
            </div>
        </div>
        @elseif($isPasantia)
        <div class="aprobacion-box">
            <div class="aprobacion-item">
                <span class="aprobacion-name">
                    <span class="check-box-icon">{{ $isPuedeSustentar ? '✓' : '' }}</span>
                    Aprobado
                </span>
                <div class="aprobacion-line">
                    @if($isPuedeSustentar)
                        <span class="aprobacion-selected-text">SELECCIONADO</span>
                    @endif
                </div>
                <span class="aprobacion-range"></span>
            </div>

            <div class="aprobacion-item">
                <span class="aprobacion-name">
                    <span class="check-box-icon">{{ $isConCorrecciones ? '✓' : '' }}</span>
                    Aprobado con observaciones
                </span>
                <div class="aprobacion-line">
                    @if($isConCorrecciones)
                        <span class="aprobacion-selected-text">SELECCIONADO</span>
                    @endif
                </div>
                <span class="aprobacion-range"></span>
            </div>

            <div class="aprobacion-item">
                <span class="aprobacion-name">
                    <span class="check-box-icon">{{ $isReestructurar ? '✓' : '' }}</span>
                    No aprobado
                </span>
                <div class="aprobacion-line">
                    @if($isReestructurar)
                        <span class="aprobacion-selected-text">SELECCIONADO</span>
                    @endif
                </div>
                <span class="aprobacion-range"></span>
            </div>
        </div>
        @else
        <div class="aprobacion-box">
            <div class="aprobacion-item">
                <span class="aprobacion-name">
                    <span class="check-box-icon">{{ $isConCorrecciones ? '✓' : '' }}</span>
                    Sustentación después de correcciones sugeridas
                </span>
                <div class="aprobacion-line">
                    @if($isConCorrecciones)
                        <span class="aprobacion-selected-text">SELECCIONADO</span>
                    @endif
                </div>
                <span class="aprobacion-range"></span>
            </div>

            <div class="aprobacion-item">
                <span class="aprobacion-name">
                    <span class="check-box-icon">{{ $isPuedeSustentar ? '✓' : '' }}</span>
                    Puede sustentar
                </span>
                <div class="aprobacion-line">
                    @if($isPuedeSustentar)
                        <span class="aprobacion-selected-text">SELECCIONADO</span>
                    @endif
                </div>
                <span class="aprobacion-range"></span>
            </div>

            <div class="aprobacion-item">
                <span class="aprobacion-name">
                    <span class="check-box-icon">{{ $isReestructurar ? '✓' : '' }}</span>
                    Requiere reestructurar y someter nuevamente
                </span>
                <div class="aprobacion-line">
                    @if($isReestructurar)
                        <span class="aprobacion-selected-text">SELECCIONADO</span>
                    @endif
                </div>
                <span class="aprobacion-range"></span>
            </div>
        </div>
        @endif

        {{-- COMENTARIOS Y OBSERVACIONES ADICIONALES (CAMPO DINÁMICO/ADAPTABLE) --}}
        <div class="comments-section">
            <div class="comments-header-label">
                COMENTARIOS Y OBSERVACIONES ADICIONALES (si es necesario por favor emplee una hoja adicional)
            </div>
            <div class="comments-border-box">{{ ($evaluacion->observaciones_por_tipo ?? [])[$evaluacion->tipo_plantilla ?? ''] ?? ($evaluacion->observaciones_globales ?? '') }}</div>
        </div>

        {{-- FIRMA(S) Y FECHA --}}
        <div class="signatures-section">
            <div class="signatures-grid">
                {{-- Evaluador --}}
                <div class="signature-col">
                    <div class="signature-line-container">
                        @if(!empty($evaluacion->firma))
                            <img src="{{ $evaluacion->firma }}" alt="Firma Evaluador" class="signature-image">
                        @endif
                        <div class="signature-line"></div>
                    </div>
                    <div class="signature-label">NOMBRE Y FIRMA DE QUIEN REVISA</div>
                    <div class="signature-name-sub">
                        {{ $evaluacion->profesor->usuario->nombre ?? ($usuario->nombre ?? '') }} {{ $evaluacion->profesor->usuario->apellido ?? ($usuario->apellido ?? '') }}
                    </div>
                    @php
                        $correoEvaluador = $evaluacion->profesor->usuario->correo ?? ($usuario->correo ?? null);
                    @endphp
                    @if(!empty($correoEvaluador))
                    <div class="signature-email-sub" style="font-size: 9.5px; font-weight: bold; color: #1e293b; margin-top: 3px;">
                        Correo: <span style="font-weight: normal; color: #334155;">{{ $correoEvaluador }}</span>
                    </div>
                    @endif
                </div>

                {{-- Fecha --}}
                <div class="signature-col" style="max-width: 180px;">
                    <div class="signature-line-container">
                        <div style="font-weight: bold; font-size: 11px; margin-bottom: 4px;">{{ $fechaEvaluacion }}</div>
                        <div class="signature-line"></div>
                    </div>
                    <div class="signature-label">FECHA</div>
                </div>
            </div>
        </div>

        {{-- PIE DE PÁGINA OFICIAL CECAR --}}
        <div class="official-footer">
            Carretera Troncal de Occidente – Vía Corozal – Sincelejo (Sucre)<br>
            Teléfonos: 280 66 65 – 280 40 18 – 280 20 32, Ext 122 – 123<br>
            Celular: (314) 524 88 16 www.cecar.edu.co Nit: 892201263 – 1<br>
            Personería Jurídica No. 7786 MEN - ICEFES
        </div>
    </div>

    <script>
        function generarPDF() {
            window.scrollTo(0,0);
            const element = document.getElementById('contenido-rubrica');
            const opt = {
                margin:       [6, 6, 6, 6],
                filename:     @json($nombreArchivoPDF),
                image:        { type: 'jpeg', quality: 0.98 },
                pagebreak:    {
                    mode:   ['css', 'legacy'],
                    avoid:  ['.header-table', '.info-table', '.eval-table tr', '.aprobacion-box', '.comments-section', '.signatures-section', '.official-footer']
                },
                html2canvas:  {
                    scale: 2,
                    scrollY: 0,
                    useCORS: true,
                    onclone: function(clonedDoc) {
                        const el = clonedDoc.getElementById('contenido-rubrica');
                        if (el) {
                            el.style.margin = '0 auto';
                            el.style.position = 'static';
                        }
                        const body = clonedDoc.body;
                        if (body) {
                            body.style.display = 'block';
                            body.style.padding = '0';
                            body.style.background = '#fff';
                        }
                    }
                },
                jsPDF: { unit: 'mm', format: 'letter', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>

