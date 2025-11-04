<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Financiero - FAO Gestión</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            background: linear-gradient(135deg, #0073e6 0%, #005bb5 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .header h1 {
            font-size: 22px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 11px;
            opacity: 0.9;
        }

        .info-section {
            background: #f8f9fa;
            padding: 12px;
            margin-bottom: 15px;
            border-left: 4px solid #0073e6;
            border-radius: 3px;
        }

        .info-section p {
            margin: 3px 0;
            font-size: 10px;
        }

        .info-section strong {
            color: #0073e6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }

        table thead {
            background: #0073e6;
            color: white;
        }

        table thead th {
            padding: 10px 5px;
            text-align: left;
            font-weight: 600;
            font-size: 9px;
            border: 1px solid #005bb5;
        }

        table tbody td {
            padding: 8px 5px;
            border: 1px solid #dee2e6;
        }

        table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        table tbody tr:hover {
            background: #e3f2fd;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .totales {
            background: #e3f2fd;
            font-weight: 700;
            border-top: 3px solid #0073e6;
        }

        .totales td {
            padding: 10px 5px;
            font-size: 10px;
        }

        .footer {
            text-align: center;
            font-size: 8px;
            color: #6c757d;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background: #e9ecef;
            border-radius: 6px;
            overflow: hidden;
            display: inline-block;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            border-radius: 6px;
            text-align: center;
            color: white;
            font-size: 8px;
            line-height: 12px;
        }

        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .stats-item {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .stats-item .value {
            font-size: 18px;
            font-weight: 700;
            color: #0073e6;
            display: block;
            margin: 5px 0;
        }

        .stats-item .label {
            font-size: 9px;
            color: #6c757d;
        }
    </style>
</head>
<body>
<!-- Header -->
<div class="header">
    <h1>📊 REPORTE FINANCIERO</h1>
    <p>Sistema de Gestión FAO - Análisis Financiero Detallado</p>
</div>

<!-- Información del Reporte -->
<div class="info-section">
    <p><strong>Fecha de Generación:</strong> {{ $fechaGeneracion }}</p>
    @if($fechaInicio && $fechaFin)
        <p><strong>Período Analizado:</strong> {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</p>
    @endif
    <p><strong>Total de Cartas:</strong> {{ $datos->count() }}</p>
</div>

<!-- Estadísticas Resumidas -->
<div class="stats-grid">
    <div class="stats-item">
        <div class="label">Presupuesto Total</div>
        <div class="value">${{ number_format($datos->sum('presupuesto_total'), 2) }}</div>
    </div>
    <div class="stats-item">
        <div class="label">Ejecutado Total</div>
        <div class="value">${{ number_format($datos->sum('ejecutado_total'), 2) }}</div>
    </div>
    <div class="stats-item">
        <div class="label">Saldo Disponible</div>
        <div class="value">${{ number_format($datos->sum('saldo'), 2) }}</div>
    </div>
    <div class="stats-item">
        <div class="label">% Ejecución Promedio</div>
        <div class="value">{{ number_format($datos->avg('porcentaje_ejecucion'), 1) }}%</div>
    </div>
</div>

<!-- Tabla de Datos -->
<table>
    <thead>
    <tr>
        <th>N°</th>
        <th>Código</th>
        <th>Proyecto</th>
        <th class="text-right">Presupuesto</th>
        <th class="text-right">Ejecutado</th>
        <th class="text-right">Saldo</th>
        <th class="text-center">% Ejecución</th>
        <th class="text-center">Progreso</th>
        <th class="text-center">Estado</th>
    </tr>
    </thead>
    <tbody>
    @foreach($datos as $index => $carta)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td>{{ $carta['codigo'] }}</td>
            <td>{{ $carta['nombre_proyecto'] }}</td>
            <td class="text-right">${{ number_format($carta['presupuesto_total'], 2) }}</td>
            <td class="text-right">${{ number_format($carta['ejecutado_total'], 2) }}</td>
            <td class="text-right">${{ number_format($carta['saldo'], 2) }}</td>
            <td class="text-center">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $carta['porcentaje_ejecucion'] }}%">
                        {{ number_format($carta['porcentaje_ejecucion'], 1) }}%
                    </div>
                </div>
            </td>
            <td class="text-center">{{ number_format($carta['progreso_promedio'], 1) }}%</td>
            <td class="text-center">
                @php
                    $estadoClass = match($carta['estado']) {
                        'en_ejecucion' => 'badge-info',
                        'finalizada' => 'badge-success',
                        'pendiente' => 'badge-warning',
                        default => 'badge-info',
                    };
                @endphp
                <span class="badge {{ $estadoClass }}">
                        {{ ucfirst(str_replace('_', ' ', $carta['estado'])) }}
                    </span>
            </td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="totales">
        <td colspan="3" class="text-right"><strong>TOTALES:</strong></td>
        <td class="text-right"><strong>${{ number_format($datos->sum('presupuesto_total'), 2) }}</strong></td>
        <td class="text-right"><strong>${{ number_format($datos->sum('ejecutado_total'), 2) }}</strong></td>
        <td class="text-right"><strong>${{ number_format($datos->sum('saldo'), 2) }}</strong></td>
        <td class="text-center"><strong>{{ number_format($datos->avg('porcentaje_ejecucion'), 1) }}%</strong></td>
        <td class="text-center"><strong>{{ number_format($datos->avg('progreso_promedio'), 1) }}%</strong></td>
        <td></td>
    </tr>
    </tfoot>
</table>

<!-- Footer -->
<div class="footer">
    <p>Este reporte fue generado automáticamente por el Sistema de Gestión FAO</p>
    <p>© {{ date('Y') }} FAO - Organización de las Naciones Unidas para la Alimentación y la Agricultura</p>
</div>
</body>
</html>
