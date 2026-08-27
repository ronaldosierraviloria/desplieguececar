<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de Registro de Propuesta de Grado</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            background-color: #f8fafc;
            padding: 40px 0;
        }
        .main-card {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #0f172a;
            padding: 28px 32px;
            text-align: left;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: -0.2px;
        }
        .header p {
            color: #94a3b8;
            margin: 4px 0 0 0;
            font-size: 13px;
        }
        .body-content {
            padding: 32px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .text {
            font-size: 14px;
            line-height: 1.6;
            color: #475569;
            margin-bottom: 24px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            background-color: #f8fafc;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .info-table td {
            padding: 12px 16px;
            font-size: 13px;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-table tr:last-child td {
            border-bottom: none;
        }
        .label {
            font-weight: 600;
            color: #475569;
            width: 35%;
        }
        .value {
            color: #0f172a;
            font-weight: 500;
        }
        .badge-code {
            display: inline-block;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            background-color: #e2e8f0;
            color: #0f172a;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
        }
        .footer {
            background-color: #f1f5f9;
            padding: 20px 32px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main-card">
            <div class="header">
                <h1>Sistema de Trabajos de Grado</h1>
                <p>Notificación Oficial de Registro</p>
            </div>
            <div class="body-content">
                <div class="greeting">Estimado(a) {{ $nombreDestinatario }},</div>
                <div class="text">
                    Le informamos que se ha registrado exitosamente en la plataforma la siguiente propuesta de grado. A continuación se presentan los detalles del registro:
                </div>

                <table class="info-table">
                    <tr>
                        <td class="label">Código Proyecto</td>
                        <td class="value"><span class="badge-code">{{ $trabajo->codigo_proyecto ?? 'N/A' }}</span></td>
                    </tr>
                    <tr>
                        <td class="label">Título del Trabajo</td>
                        <td class="value">{{ $trabajo->titulo }}</td>
                    </tr>
                    <tr>
                        <td class="label">Modalidad</td>
                        <td class="value">{{ $trabajo->tipo->nombre_tipo ?? 'Propuesta de Grado' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Fecha de Subida</td>
                        <td class="value">{{ \Carbon\Carbon::parse($trabajo->fecha_subida)->format('d/m/Y') }}</td>
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
                    @if($trabajo->directores->count() > 0)
                    <tr>
                        <td class="label">Director(es)</td>
                        <td class="value">
                            @foreach($trabajo->directores as $dir)
                                {{ $dir->nombre }} {{ $dir->apellido }}@if(!$loop->last), @endif
                            @endforeach
                        </td>
                    </tr>
                    @endif
                </table>

                <div class="text">
                    El proyecto entrará en el proceso institucional de revisión y evaluación correspondiente. Podrá darle seguimiento a través del sistema.
                </div>
            </div>
            <div class="footer">
                Este es un mensaje automático generado por el Sistema de Gestión de Trabajos de Grado.<br> Por favor no responda a esta dirección de correo.
            </div>
        </div>
    </div>
</body>
</html>
