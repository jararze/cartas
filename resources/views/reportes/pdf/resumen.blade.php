<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Resumen - FAO Gestión</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }

        .header {
            background: linear-gradient(135deg, #0073e6 0%, #005bb5 100%);
            color: white;
            padding: 25px;
            text-align: center;
            margin-bottom: 25px;
            border-radius: 5px;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .header p {
            font-size: 12px;
            opacity: 0.9;
        }

        .info-section {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #0073e6;
            border-radius: 3px;
        }

        .info-section p {
            margin: 5px 0;
            font-size: 11px;
        }

        .info-section strong {
            color: #0073e6;
        }

        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }

        .stats-row {
            display: table-row;
        }

        .stats-item {
            display: table-cell;
            width: 25%;
            padding: 15px;
            text-align: center;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
        }

        .stats-item .value {
            font-size: 28px;
            font-weight: 700;
            color: #0073e6;
            display: block;
            margin: 8px 0;
        }

        .stats-item .label {
            font-size: 10px;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #0073e6;
            margin: 25px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #0073e6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table thead {
            background: #0073e6;
            color: white;
        }

        table thead th {
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
            border: 1px solid #005bb5;
        }

        table tbody td {
            padding: 10px 8px;
            border: 1px solid #dee2e6;
            font-size: 10px;
        }

        table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 9px;
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

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .footer {
            text-align: center;
            font-size: 9px;
            color: #6c757d;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }

        .alert-box {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }

        .alert-danger {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }

        .alert-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }

        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
    </style>
</head>
<body>
<!-- Header -->
<div class="header">
    <h1>📊 REPORTE RESUMEN EJECUTIVO</h1>
    <p>Sistema de Gestión FAO - Resumen General del Sistema</p>
</div>

<!-- Información del Reporte -->
<div class="info-section">
    <p><strong>Fecha de Generación:</strong> {{ $fechaGeneracion }}</p>
    @if(isset($fechaInicio) && $fechaInicio)
        <p><strong>Período Analizado:</strong> {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</p>
    @endif
</div>

<!-- Estadísticas Generales -->
<h2 class="section-title">Estadísticas Generales</h2>

<div class="stats-grid">
    <div class="stats-row">
        <div class="stats-item">
            <div class="label">Total Cartas</div>
            <div class="value">{{ $datos['estadisticas']['total_cartas'] }}</div>
        </div>
        <div class="stats-item">
            <div class="label">Cartas Activas</div>
            <div class="value">{{ $datos['estadisticas']['cartas_activas'] }}</div>
        </div>
        <div class="stats-item">
            <div class="label">Cartas Finalizadas</div>
            <div class="value">{{ $datos['estadisticas']['cartas_finalizadas'] }}</div>
        </div>
        <div class="stats-item">
            <div class="label">Progreso Promedio</div>
            <div class="value">{{ number_format($datos['estadisticas']['progreso_promedio'], 1) }}%</div>
        </div>
    </div>
</div>

<!-- Presupuesto -->
<h2 class="section-title">Resumen Presupuestario</h2>

<table>
    <thead>
    <tr>
        <th>Concepto</th>
        <th class="text-right">Monto (USD)</th>
        <th class="text-center">Porcentaje</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td><strong>Presupuesto Total</strong></td>
        <td class="text-right"><strong>${{ number_format($datos['estadisticas']['total_presupuesto'], 2) }}</strong></td>
        <td class="text-center">100%</td>
    </tr>
    <tr>
        <td>Ejecutado</td>
        <td class="text-right">${{ number_format($datos['estadisticas']['total_ejecutado'], 2) }}</td>
        <td class="text-center">{{ number_format($datos['estadisticas']['ejecucion_presupuestaria'], 2) }}%</td>
    </tr>
    <tr>
        <td>Saldo Disponible</td>
        <td class="text-right">${{ number_format($datos['estadisticas']['saldo_disponible'], 2) }}</td>
        <td class="text-center">{{ number_format(100 - $datos['estadisticas']['ejecucion_presupuestaria'], 2) }}%</td>
    </tr>
    </tbody>
</table>

<!-- Actividades -->
<h2 class="section-title">Estado de Actividades</h2>

<table>
    <thead>
    <tr>
        <th>Estado</th>
        <th class="text-center">Cantidad</th>
        <th class="text-center">Porcentaje</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td><span class="badge badge-success">Completadas</span></td>
        <td class="text-center">{{ $datos['estadisticas']['actividades_completadas'] }}</td>
        <td class="text-center">{{ $datos['estadisticas']['actividades_total'] > 0 ? number_format(($datos['estadisticas']['actividades_completadas'] / $datos['estadisticas']['actividades_total']) * 100, 1) : 0 }}%</td>
    </tr>
    <tr>
        <td><span class="badge badge-warning">En Curso</span></td>
        <td class="text-center">{{ $datos['estadisticas']['actividades_en_curso'] }}</td>
        <td class="text-center">{{ $datos['estadisticas']['actividades_total'] > 0 ? number_format(($datos['estadisticas']['actividades_en_curso'] / $datos['estadisticas']['actividades_total']) * 100, 1) : 0 }}%</td>
    </tr>
    <tr>
        <td><span class="badge">Pendientes</span></td>
        <td class="text-center">{{ $datos['estadisticas']['actividades_pendientes'] }}</td>
        <td class="text-center">{{ $datos['estadisticas']['actividades_total'] > 0 ? number_format(($datos['estadisticas']['actividades_pendientes'] / $datos['estadisticas']['actividades_total']) * 100, 1) : 0 }}%</td>
    </tr>
    <tr>
        <td><span class="badge badge-danger">Atrasadas</span></td>
        <td class="text-center">{{ $datos['estadisticas']['actividades_atrasadas'] }}</td>
        <td class="text-center">{{ $datos['estadisticas']['actividades_total'] > 0 ? number_format(($datos['estadisticas']['actividades_atrasadas'] / $datos['estadisticas']['actividades_total']) * 100, 1) : 0 }}%</td>
    </tr>
    <tr style="font-weight: bold; background: #e3f2fd;">
        <td>TOTAL</td>
        <td class="text-center">{{ $datos['estadisticas']['actividades_total'] }}</td>
        <td class="text-center">100%</td>
    </tr>
    </tbody>
</table>

<!-- Alertas -->
@if($datos['alertas']['total_alertas'] > 0)
    <h2 class="section-title">⚠️ Alertas y Riesgos</h2>

    @if($datos['alertas']['atrasadas'] > 0)
        <div class="alert-box alert-danger">
            <strong>🚨 Actividades Atrasadas:</strong> {{ $datos['alertas']['atrasadas'] }} actividades requieren atención inmediata
        </div>
    @endif

    @if($datos['alertas']['exceden_presupuesto'] > 0)
        <div class="alert-box alert-danger">
            <strong>💰 Sobrepresupuesto:</strong> {{ $datos['alertas']['exceden_presupuesto'] }} actividades han excedido su presupuesto
        </div>
    @endif

    @if($datos['alertas']['riesgo_alto'] > 0)
        <div class="alert-box alert-warning">
            <strong>⚡ Riesgo Alto:</strong> {{ $datos['alertas']['riesgo_alto'] }} actividades presentan riesgo alto o crítico
        </div>
    @endif

    @if($datos['alertas']['proximas_vencer'] > 0)
        <div class="alert-box alert-warning">
            <strong>⏰ Próximas a Vencer:</strong> {{ $datos['alertas']['proximas_vencer'] }} actividades vencen en los próximos 7 días
        </div>
    @endif
@else
    <div class="alert-box alert-success">
        <strong>✅ Sistema Saludable:</strong> No hay alertas críticas en este momento
    </div>
@endif

<!-- Footer -->
<div class="footer">
    <p><strong>Este reporte fue generado automáticamente por el Sistema de Gestión FAO</strong></p>
    <p>© {{ date('Y') }} FAO - Organización de las Naciones Unidas para la Alimentación y la Agricultura</p>
    <p>Generado el: {{ $fechaGeneracion }}</p>
</div>
</body>
</html>
