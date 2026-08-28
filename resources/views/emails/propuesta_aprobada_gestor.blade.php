<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propuesta Aprobada - Gestor</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 40px 0; }
        .main-card { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .header { background-color: #166534; padding: 28px 32px; color: #ffffff; }
        .header h1 { margin: 0; font-size: 18px; font-weight: 600; }
        .header p { color: #bbf7d0; margin: 4px 0 0 0; font-size: 13px; }
        .body-content { padding: 32px; }
        .greeting { font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 12px; }
        .text { font-size: 14px; line-height: 1.6; color: #475569; margin-bottom: 24px; }
        .alert-box { background-color: #f0fdf4; border-left: 4px solid #22c55e; padding: 16px; border-radius: 4px; margin-bottom: 24px; font-size: 14px; color: #15803d; }
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
                <p>Propuesta Aprobada - Fase de Informe Final</p>
            </div>
            <div class="body-content">
                <div class="greeting">Estimado(a) Gestor(a) {{ $nombreGestor }},</div>
                <div class="text">
                    La calificación final emitida por ambos evaluadores para la siguiente propuesta de grado es **óptima y aprobatoria**, sin requerir modificaciones adicionales:
                </div>

                <div class="alert-box">
                    <strong>Acción Requerida para Gestor:</strong> Debe estar pendiente en el sistema para que los estudiantes realicen el envío del <strong>Informe Final de Trabajo de Grado</strong>.
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
                    @if($trabajo->estudiante->count() > 0)
                    <tr>
                        <td class="label">Estudiante(s)</td>
                        <td class="value">
                            @foreach($trabajo->estudiante as $est)
                                {{ $est->nombre }} {{ $est->apellido }}@if(!$loop->last), @endif
                            @endforeach
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
            <div class="footer">
                Mensaje automático generado por el Sistema de Gestión de Trabajos de Grado.
            </div>
        </div>
    </div>
</body>
</html>
