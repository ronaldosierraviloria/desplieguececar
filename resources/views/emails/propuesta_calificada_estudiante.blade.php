<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado de Evaluación de Propuesta</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 40px 0; }
        .main-card { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .header { background-color: #0f172a; padding: 28px 32px; color: #ffffff; }
        .header h1 { margin: 0; font-size: 18px; font-weight: 600; }
        .header p { color: #94a3b8; margin: 4px 0 0 0; font-size: 13px; }
        .body-content { padding: 32px; }
        .greeting { font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 12px; }
        .text { font-size: 14px; line-height: 1.6; color: #475569; margin-bottom: 24px; }
        .grade-card { background-color: #f1f5f9; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 24px; border: 1px solid #cbd5e1; }
        .grade-title { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: 700; margin-bottom: 6px; }
        .grade-number { font-size: 32px; font-weight: 800; color: #0f172a; }
        .badge-status { display: inline-block; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 13px; margin-top: 8px; text-transform: uppercase; }
        .status-aceptada { background-color: #dcfce7; color: #166534; }
        .status-mejoras { background-color: #fef9c3; color: #854d0e; }
        .status-rechazada { background-color: #fee2e2; color: #991b1b; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; background-color: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0; }
        .info-table td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #e2e8f0; }
        .info-table tr:last-child td { border-bottom: none; }
        .label { font-weight: 600; color: #475569; width: 35%; }
        .value { color: #0f172a; font-weight: 500; }
        .btn { display: inline-block; background-color: #2563eb; color: #ffffff; padding: 12px 24px; border-radius: 6px; font-weight: 600; text-decoration: none; font-size: 14px; }
        .footer { background-color: #f1f5f9; padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main-card">
            <div class="header">
                <h1>Sistema de Trabajos de Grado</h1>
                <p>Dictamen de Evaluación de Propuesta</p>
            </div>
            <div class="body-content">
                <div class="greeting">Estimado(a) {{ $nombreEstudiante }},</div>
                <div class="text">
                    Los evaluadores asignados han finalizado el proceso de evaluación de su propuesta de grado:
                </div>

                <div class="grade-card">
                    <div class="grade-title">Nota Promedio Definitiva</div>
                    <div class="grade-number">{{ $notaPromedio !== null ? number_format($notaPromedio, 2) : 'N/A' }}</div>
                    <div>
                        @php
                            $resLower = strtolower($resultado);
                            $badgeClass = 'status-aceptada';
                            $resText = 'Aceptada / Aprobada';
                            if (str_contains($resLower, 'mejora') || str_contains($resLower, 'correc')) {
                                $badgeClass = 'status-mejoras';
                                $resText = 'Aceptada con Correcciones / Mejoras';
                            } elseif (str_contains($resLower, 'rechaz')) {
                                $badgeClass = 'status-rechazada';
                                $resText = 'Rechazada';
                            }
                        @endphp
                        <span class="badge-status {{ $badgeClass }}">{{ $resText }}</span>
                    </div>
                </div>

                <table class="info-table">
                    <tr>
                        <td class="label">Código Proyecto</td>
                        <td class="value">{{ $trabajo->codigo_proyecto ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Título</td>
                        <td class="value">{{ $trabajo->titulo }}</td>
                    </tr>
                    @if(!empty($observaciones))
                    <tr>
                        <td class="label">Observaciones</td>
                        <td class="value">{{ $observaciones }}</td>
                    </tr>
                    @endif
                </table>

                <div class="text">
                    Adjunto a este correo electrónico encontrará el documento en formato <strong>PDF con la Rúbrica Completa y Detallada de Evaluación</strong> emitida por el comité evaluador.
                </div>
            </div>
            <div class="footer">
                Mensaje automático generado por el Sistema de Gestión de Trabajos de Grado.
            </div>
        </div>
    </div>
</body>
</html>
