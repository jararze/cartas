<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte por Líneas Presupuestarias</title>
    <style>
        * {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
        }
        body {
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #0073e6;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            color: #0073e6;
            margin: 0;
        }
        .header p {
            color: #666;
            margin: 5px 0 0 0;
        }
        .meta-info {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .meta-info table {
            width: 100%;
        }
        .meta-info td {
            padding: 3px 10px;
        }
        .linea-header {
            background: #0073e6;
            color: white;
            padding: 10px;
            margin-top: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .linea-summary {
            background: #e3f2fd;
            padding: 8px 10px;
            margin-bottom: 10px;
        }
        .linea-summary span {
            margin-right: 20px;
        }
        .carta-header {
            background: #e3f2fd;
            padding: 8px 10px;
            font-weight: bold;
            border-left: 4px solid #0073e6;
            margin: 10px 0 5px 0;
        }
        .producto-header {
            background: #f5f5f5;
            padding: 6px 10px;
            font-weight: bold;
            margin: 5px 0;
            font-size: 9px;
        }
        table.actividades {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0 15px 0;
        }
        table.actividades th {
            background: #e0e0e0;
            padding: 6px;
            text-align: left;
            font-size: 8px;
            border: 1px solid #ccc;
        }
        table.actividades td {
            padding: 5px;
            border: 1px solid #ddd;
            font-size: 8px;
        }
        table.actividades tr:nth-child(even) {
            background: #fafafa;
        }
        .subtotal {
            font-weight: bold;
            background: #f0f0f0;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-danger {
            color: #d32f2f;
        }
        .text-success {
            color: #388e3c;
        }
        .text-warning {
            color: #f57c00;
        }
        .totales-generales {
            background: #1565c0;
            color: white;
            padding: 15px;
            margin-top: 20px;
        }
        .totales-generales h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        .totales-generales table {
            width: 100%;
        }
        .totales-generales td {
            padding: 5px 10px;
            font-size: 11px;
        }
        .estado-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7px;
            text-transform: uppercase;
        }
        .estado-finalizado { background: #c8e6c9; color: #2e7d32; }
        .estado-en_curso { background: #bbdefb; color: #1565c0; }
        .estado-pendiente { background: #f5f5f5; color: #616161; }
        .estado-atrasado { background: #ffcdd2; color: #c62828; }
        .page-break {
            page-break-before: always;
        }
        .footer {
            position: fixed;
            bottom: 10px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #999;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>📊 REPORTE POR LÍNEAS PRESUPUESTARIAS</h1>
    <p>Sistema de Gestión FAO - Cartas Documento</p>
</div>

<div class="meta-info">
    <table>
        <tr>
            <td><strong>Fecha de generación:</strong> {{ $fechaGeneracion }}</td>
            <td><strong>Total de líneas:</strong> {{ $datos['resumen']['total_lineas'] }}</td>
            <td><strong>Total actividades:</strong> {{ $datos['resumen']['total_actividades'] }}</td>
        </tr>
        @if($fechaInicio || $fechaFin)
            <tr>
                <td colspan="3">
                    <strong>Periodo:</strong>
                    {{ $fechaInicio ? \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') : 'Inicio' }}
                    -
                    {{ $fechaFin ? \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') : 'Actual' }}
                </td>
            </tr>
        @endif
    </table>
</div>

@foreach($datos['lineas'] as $linea)
    <div class="linea-header">
        📁 LÍNEA PRESUPUESTARIA: {{ strtoupper($linea['linea_presupuestaria']) }}
    </div>

    <div class="linea-summary">
        <span><strong>Planificado:</strong> ${{ number_format($linea['total_planificado'], 2) }}</span>
        <span><strong>Ejecutado:</strong> ${{ number_format($linea['total_ejecutado'], 2) }}</span>
        <span class="{{ $linea['total_saldo'] < 0 ? 'text-danger' : '' }}">
                <strong>Saldo:</strong> ${{ number_format($linea['total_saldo'], 2) }}
            </span>
        <span><strong>Ejecución:</strong> {{ number_format($linea['porcentaje_ejecucion'], 1) }}%</span>
        <span><strong>Actividades:</strong> {{ $linea['cantidad_actividades'] }}</span>
    </div>

    @foreach($linea['cartas'] as $carta)
        <div class="carta-header">
            📋 {{ $carta['carta_codigo'] }} - {{ $carta['carta_nombre'] }}
            <span style="float: right; font-size: 9px;">
                    Plan: ${{ number_format($carta['planificado'], 2) }} |
                    Ejec: ${{ number_format($carta['ejecutado'], 2) }} |
                    {{ number_format($carta['porcentaje_ejecucion'], 1) }}%
                </span>
        </div>

        @foreach($carta['productos'] as $producto)
            <div class="producto-header">
                📦 {{ $producto['producto_nombre'] }}
                <span style="float: right;">
                        Progreso: {{ number_format($producto['progreso'], 1) }}%
                    </span>
            </div>

            <table class="actividades">
                <thead>
                <tr>
                    <th style="width: 25%;">Actividad</th>
                    <th style="width: 12%;">Planificado</th>
                    <th style="width: 12%;">Ejecutado</th>
                    <th style="width: 12%;">Saldo</th>
                    <th style="width: 8%;">% Ejec.</th>
                    <th style="width: 8%;">Progreso</th>
                    <th style="width: 10%;">Estado</th>
                    <th style="width: 13%;">Responsable</th>
                </tr>
                </thead>
                <tbody>
                @foreach($producto['actividades'] as $actividad)
                    <tr>
                        <td>{{ $actividad['nombre'] }}</td>
                        <td class="text-right">${{ number_format($actividad['planificado'], 2) }}</td>
                        <td class="text-right">${{ number_format($actividad['ejecutado'], 2) }}</td>
                        <td class="text-right {{ $actividad['saldo'] < 0 ? 'text-danger' : '' }}">
                            ${{ number_format($actividad['saldo'], 2) }}
                        </td>
                        <td class="text-center">
                            @php
                                $porcEjec = $actividad['planificado'] > 0
                                    ? ($actividad['ejecutado'] / $actividad['planificado']) * 100 : 0;
                            @endphp
                            {{ number_format($porcEjec, 1) }}%
                        </td>
                        <td class="text-center">{{ number_format($actividad['progreso'], 1) }}%</td>
                        <td class="text-center">
                                    <span class="estado-badge estado-{{ $actividad['estado'] }}">
                                        {{ ucfirst(str_replace('_', ' ', $actividad['estado'])) }}
                                    </span>
                        </td>
                        <td>{{ $actividad['responsable'] }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal">
                    <td><strong>Subtotal Producto</strong></td>
                    <td class="text-right"><strong>${{ number_format($producto['planificado'], 2) }}</strong></td>
                    <td class="text-right"><strong>${{ number_format($producto['ejecutado'], 2) }}</strong></td>
                    <td class="text-right {{ $producto['saldo'] < 0 ? 'text-danger' : '' }}">
                        <strong>${{ number_format($producto['saldo'], 2) }}</strong>
                    </td>
                    <td colspan="4"></td>
                </tr>
                </tbody>
            </table>
        @endforeach
    @endforeach
@endforeach

<div class="totales-generales">
    <h3>📊 TOTALES GENERALES</h3>
    <table>
        <tr>
            <td><strong>Total Planificado:</strong></td>
            <td>${{ number_format($datos['totales']['planificado'], 2) }}</td>
            <td><strong>Total Ejecutado:</strong></td>
            <td>${{ number_format($datos['totales']['ejecutado'], 2) }}</td>
            <td><strong>Saldo Global:</strong></td>
            <td>${{ number_format($datos['totales']['saldo'], 2) }}</td>
        </tr>
        <tr>
            <td><strong>% Ejecución Global:</strong></td>
            <td colspan="5">{{ number_format($datos['resumen']['porcentaje_ejecucion_global'], 2) }}%</td>
        </tr>
    </table>
</div>

<div class="footer">
    Reporte generado automáticamente por el Sistema de Gestión FAO - {{ $fechaGeneracion }}
</div>
</body>
</html>
