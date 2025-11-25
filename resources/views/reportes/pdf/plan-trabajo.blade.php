<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plan de Trabajo - FAO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 9px; color: #333; line-height: 1.3; }
        .header { background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%); color: white; padding: 20px; text-align: center; margin-bottom: 20px; border-radius: 5px; }
        .header h1 { font-size: 22px; margin-bottom: 5px; }
        .header p { font-size: 11px; opacity: 0.9; }
        .carta-header { background: #f0f9ff; border-left: 4px solid #0891b2; padding: 10px; margin: 15px 0 10px 0; }
        .carta-header h2 { font-size: 14px; color: #0891b2; margin-bottom: 5px; }
        .carta-header p { font-size: 9px; color: #64748b; }
        .producto-section { background: #e0f2fe; padding: 8px; margin: 10px 0; border-left: 3px solid #0284c7; }
        .producto-section h3 { font-size: 11px; color: #0284c7; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 8px; }
        table thead { background: #0891b2; color: white; }
        table thead th { padding: 8px 4px; text-align: left; font-weight: 600; font-size: 8px; border: 1px solid #0e7490; }
        table tbody td { padding: 6px 4px; border: 1px solid #dee2e6; }
        table tbody tr:nth-child(even) { background: #f8f9fa; }
        table tbody tr.actividad-row { background: white; }
        table tbody tr.actividad-row:hover { background: #e0f2fe; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 7px; font-weight: 600; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .footer { text-align: center; font-size: 8px; color: #6c757d; margin-top: 30px; padding-top: 15px; border-top: 1px solid #dee2e6; page-break-inside: avoid; }
        .progress-bar { width: 100%; height: 10px; background: #e9ecef; border-radius: 5px; overflow: hidden; display: inline-block; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #0891b2 0%, #06b6d4 100%); border-radius: 5px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
<div class="header">
    <h1>📅 PLAN DE TRABAJO</h1>
    <p>Sistema de Gestión FAO - Plan de Trabajo Detallado</p>
</div>

@foreach($datos as $indexCarta => $carta)
    @if($indexCarta > 0)
        <div class="page-break"></div>
    @endif

    <div class="carta-header">
        <h2>CARTA: {{ $carta['carta_codigo'] }}</h2>
        <p><strong>Proyecto:</strong> {{ $carta['carta_nombre'] }}</p>
        <p><strong>Período:</strong> {{ $carta['carta_fecha_inicio']->format('d/m/Y') }} - {{ $carta['carta_fecha_fin']->format('d/m/Y') }} |
            <strong>Presupuesto Total:</strong> ${{ number_format($carta['carta_presupuesto'], 2) }}</p>
    </div>

    @foreach($carta['productos'] as $producto)
        <div class="producto-section">
            <h3>📦 {{ $producto['nombre'] }}</h3>
            <p style="font-size: 8px;">{{ $producto['descripcion'] }}</p>
            <p style="font-size: 8px; margin-top: 3px;">
                <strong>Período:</strong> {{ $producto['fecha_inicio']->format('d/m/Y') }} - {{ $producto['fecha_fin']->format('d/m/Y') }} |
                <strong>Presupuesto:</strong> ${{ number_format($producto['presupuesto'], 2) }}
            </p>
        </div>

        <table>
            <thead>
            <tr>
                <th style="width: 20%;">Actividad</th>
                <th style="width: 15%;">Responsable</th>
                <th style="width: 8%;" class="text-center">Inicio</th>
                <th style="width: 8%;" class="text-center">Fin</th>
                <th style="width: 8%;" class="text-right">Duración</th>
                <th style="width: 10%;" class="text-right">Presupuesto</th>
                <th style="width: 10%;" class="text-right">Ejecutado</th>
                <th style="width: 11%;" class="text-center">Progreso</th>
                <th style="width: 10%;" class="text-center">Estado</th>
            </tr>
            </thead>
            <tbody>
            @foreach($producto['actividades'] as $actividad)
                <tr class="actividad-row">
                    <td>{{ $actividad['nombre'] }}</td>
                    <td>{{ $actividad['responsable'] }}</td>
                    <td class="text-center">{{ $actividad['fecha_inicio']->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $actividad['fecha_fin']->format('d/m/Y') }}</td>
                    <td class="text-right">{{ $actividad['duracion_dias'] }} días</td>
                    <td class="text-right">${{ number_format($actividad['presupuesto'], 2) }}</td>
                    <td class="text-right">${{ number_format($actividad['ejecutado'], 2) }}</td>
                    <td class="text-center">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $actividad['progreso'] }}%"></div>
                        </div>
                        <div style="font-size: 7px; margin-top: 2px;">{{ number_format($actividad['progreso'], 0) }}%</div>
                    </td>
                    <td class="text-center">
                        @php
                            $badgeClass = match($actividad['estado']) {
                                'finalizado' => 'badge-success',
                                'en_curso' => 'badge-info',
                                'atrasado' => 'badge-danger',
                                default => 'badge-warning',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">
                            {{ ucfirst(str_replace('_', ' ', $actividad['estado'])) }}
                        </span>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endforeach
@endforeach

<div class="footer">
    <p><strong>Este reporte fue generado automáticamente por el Sistema de Gestión FAO</strong></p>
    <p>© {{ date('Y') }} FAO - Organización de las Naciones Unidas para la Alimentación y la Agricultura</p>
    <p>Para ver el Diagrama de Gantt, genere este reporte en formato Excel</p>
</div>
</body>
</html>
