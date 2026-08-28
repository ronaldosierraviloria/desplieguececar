<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Re-evaluación Finalizada</title>
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
                <p>Dictamen de Re-evaluación</p>
            </div>
            <div class="body-content">
                <div class="greeting">Estimado(a) {{ $nombreEstudiante }},</div>
                <div class="text">
                    Le informamos que la re-evaluación del documento corregido de su proyecto de grado ha sido completada por el evaluador / comité correspondiente.
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
                    <tr>
                        <td class="label">Resultado</td>
                        <td class="value"><strong>{{ $resultado }}</strong></td>
                    </tr>
                </table>

                <div class="text">
                    Puede consultar los detalles actualizados y la rúbrica ingresando a la plataforma.
                </div>
            </div>
            <div class="footer">
                Mensaje automático generado por el Sistema de Gestión de Trabajos de Grado.
            </div>
        </div>
    </div>
</body>
</html>
