<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rúbrica Descargable PDF - Informe Final</title>
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
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; background-color: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0; }
        .info-table td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #e2e8f0; }
        .info-table tr:last-child td { border-bottom: none; }
        .label { font-weight: 600; color: #475569; width: 35%; }
        .value { color: #0f172a; font-weight: 500; }
        .footer { background-color: #f1f5f9; padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main-card">
            <div class="header">
                <h1>Sistema de Trabajos de Grado</h1>
                <p>Rúbrica de Evaluación de Informe Final</p>
            </div>
            <div class="body-content">
                <div class="greeting">Estimado(a) {{ $nombreEstudiante }},</div>
                <div class="text">
                    Le remitimos adjunto a este correo electrónico la **Rúbrica Descargable en PDF** correspondiente a la evaluación del **Informe Final de su Trabajo de Grado**, donde podrá consultar las observaciones y retroalimentaciones detalladas del jurado evaluador.
                </div>

                <div class="grade-card">
                    <div class="grade-title">Nota Final Obtenida</div>
                    <div class="grade-number">{{ $notaFinal !== null ? number_format($notaFinal, 2) : 'N/A' }}</div>
                    <div style="font-weight: 600; color: #1e293b; margin-top: 4px;">Dictamen: {{ ucfirst($resultado) }}</div>
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
                    Consulte el archivo PDF adjunto para conocer la calificación desglosada por criterio.
                </div>
            </div>
            <div class="footer">
                Mensaje automático generado por el Sistema de Gestión de Trabajos de Grado.
            </div>
        </div>
    </div>
</body>
</html>
