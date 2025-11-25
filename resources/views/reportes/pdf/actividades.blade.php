
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Actividades - FAO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 9px; color: #333; line-height: 1.3; }
        .header { background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%); color: white; padding: 20px; text-align: center; margin-bottom: 20px; border-radius: 5px; }
        .header h1 { font-size: 22px; margin-bottom: 5px; }
        .header p { font-size: 11px; opacity: 0.9; }
        .info-section { background: #f8f9fa; padding: 12px; margin-bottom: 15px; border-left: 4px solid #ea580c; border-radius: 3px; }
        .info-section p { margin: 3px 0; font-size: 10px; }
        .info-section strong { color: #ea580c; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 8px; }
        table thead { background: #ea580c; color: white; }
        table thead th { padding: 8px 4px; text-align: left; font-weight: 600; font-size: 8px; border: 1px solid #c2410c; }
        table tbody td { padding: 6px 4px; border: 1px solid #dee2e6; }
        table tbody tr:nth-child(even) { background: #f8f9fa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 7px; font-weight: 600; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .footer { text-align: center; font-size: 8px; color: #6c757d; margin-top: 30px; padding-top: 15px; border-top: 1px solid #dee2e6; }
        .progress-bar { width: 100%; height: 10px; background: #e9ecef; border-radius: 5px; overflow: hidden; display: inline-block; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #ea580c 0%, #fb923c 100%); border-radius: 5px; }
    </style>
</head>
<body>
<div class="header">
    <h1>📋 LISTA DE ACTIVIDADES</h1>
    <p>Sistema de Gestión FAO - Detalle Completo de Actividades</p>
</div>

<div class="info-section">
    <p><strong>Fecha de Generación:</strong> {{ $fechaGeneracion }}</p>
    @if($fechaInicio && $fechaFin)
        <p><strong>Período Analizado:</strong> {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</p>
    @endif
    <p><strong>Total de Actividades:</strong> {{ $datos->count() }}</p>
</div>

<table>
    <thead>
    <tr>
        <th style="width: 8%;">Carta</th>
        <th style="width: 12%;">Producto</th>
        <th style="width: 15%;">Actividad</th>
        <th style="width: 10%;">Responsable</th>
        <th style="width: 8%;" class="text-center">Inicio</th>
        <th style="width: 8%;" class="text-center">Fin</th>
        <th style="width: 8%;" class="text-right">Presup.</th>
        <th style="width: 8%;" class="text-right">Ejecutado</th>
        <th style="width: 8%;" class="text-right">Saldo</th>
        <th style="width: 8%;" class="text-center">Progreso</th>
        <th style="width: 7%;" class="text-center">Estado</th>
    </tr>
    </thead>
    <tbody>
    @foreach($datos as $item)
        <tr>
            <td>{{ $item['carta_codigo'] }}</td>
            <td>{{ $item['producto'] }}</td>
            <td>{{ $item['actividad'] }}</td>
            <td>{{ $item['responsable'] }}</td>
            <td class="text-center">{{ $item['fecha_inicio'] }}</td>
            <td class="text-center">{{ $item['fecha_fin'] }}</td>
            <td class="text-right">${{ number_format($item['presupuesto'], 2) }}</td>
            <td class="text-right">${{ number_format($item['ejecutado'], 2) }}</td>
            <td class="text-right {{ $item['saldo'] < 0 ? 'text-negative' : '' }}">${{ number_format($item['saldo'], 2) }}</td>
            <td class="text-center">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $item['progreso'] }}%"></div>
                </div>
                <div style="font-size: 7px; margin-top: 2px;">{{ number_format($item['progreso'], 0) }}%</div>
            </td>
            <td class="text-center">
                @php
                    $badgeClass = match($item['estado']) {
                        'finalizado' => 'badge-success',
                        'en_curso' => 'badge-info',
                        'atrasado' => 'badge-danger',
                        default => 'badge-warning',
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">
                    {{ ucfirst(str_replace('_', ' ', $item['estado'])) }}
                </span>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    <p>Este reporte fue generado automáticamente por el Sistema de Gestión FAO</p>
    <p>© {{ date('Y') }} FAO - Organización de las Naciones Unidas para la Alimentación y la Agricultura</p>
</div>
</body>
</html>
