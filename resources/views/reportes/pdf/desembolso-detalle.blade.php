<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Desembolso #{{ $desembolso->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        .header {
            border-bottom: 3px solid #0073e6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .logo-section {
            font-size: 18px;
            font-weight: bold;
            color: #0073e6;
        }
        .documento-info {
            text-align: right;
            font-size: 10px;
            color: #666;
        }
        .titulo {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-top: 10px;
        }
        .subtitulo {
            font-size: 11px;
            color: #666;
        }
        .estado-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .estado-pendiente { background: #fef3c7; color: #92400e; }
        .estado-en_proceso { background: #dbeafe; color: #1e40af; }
        .estado-pagado { background: #d1fae5; color: #065f46; }
        .estado-rechazado { background: #fee2e2; color: #991b1b; }

        .seccion {
            margin-bottom: 20px;
        }
        .seccion-titulo {
            font-size: 12px;
            font-weight: bold;
            color: #0073e6;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 150px;
            padding: 5px 10px 5px 0;
            font-weight: bold;
            color: #666;
            font-size: 10px;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
            color: #333;
        }
        .monto-principal {
            font-size: 24px;
            font-weight: bold;
            color: #059669;
            margin: 10px 0;
        }
        table.tabla-datos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 10px;
        }
        table.tabla-datos th {
            background: #f3f4f6;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #e5e7eb;
        }
        table.tabla-datos td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.tabla-datos tr:nth-child(even) {
            background: #f9fafb;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-green { color: #059669; }
        .text-blue { color: #2563eb; }
        .text-red { color: #dc2626; }

        .resumen-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
        .progress-bar {
            background: #e5e7eb;
            border-radius: 4px;
            height: 8px;
            width: 80px;
            display: inline-block;
            vertical-align: middle;
        }
        .progress-fill {
            background: #0073e6;
            height: 100%;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<!-- Header -->
<div class="header">
    <table width="100%">
        <tr>
            <td width="60%">
                <div class="logo-section">FAO Bolivia - Sistema de Gestión</div>
                <div class="titulo">Detalle de Desembolso #{{ $desembolso->id }}</div>
                <div class="subtitulo">{{ $desembolso->carta->codigo }} - {{ $desembolso->carta->nombre_proyecto }}</div>
            </td>
            <td width="40%" style="text-align: right; vertical-align: top;">
                <div class="documento-info">
                    Generado: {{ now()->format('d/m/Y H:i') }}<br>
                    <span class="estado-badge estado-{{ $desembolso->estado }}">
                            {{ ucfirst(str_replace('_', ' ', $desembolso->estado)) }}
                        </span>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- Monto Principal -->
<div class="resumen-box">
    <table width="100%">
        <tr>
            <td width="50%">
                <div style="font-size: 10px; color: #666;">MONTO SOLICITADO</div>
                <div class="monto-principal">${{ number_format($desembolso->monto_total, 2) }}</div>
            </td>
            <td width="50%" style="text-align: right;">
                @if($desembolso->monto_aprobado)
                    <div style="font-size: 10px; color: #666;">MONTO APROBADO</div>
                    <div style="font-size: 20px; font-weight: bold; color: #059669;">${{ number_format($desembolso->monto_aprobado, 2) }}</div>
                @endif
            </td>
        </tr>
    </table>
</div>

<!-- Información General -->
<div class="seccion">
    <div class="seccion-titulo">Información General</div>
    <table width="100%" style="font-size: 10px;">
        <tr>
            <td width="50%" style="vertical-align: top; padding-right: 20px;">
                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-label">Carta:</span>
                        <span class="info-value">{{ $desembolso->carta->codigo }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Producto:</span>
                        <span class="info-value">{{ $desembolso->producto->nombre }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Solicitado por:</span>
                        <span class="info-value">{{ $desembolso->solicitante?->name ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fecha Solicitud:</span>
                        <span class="info-value">{{ $desembolso->fecha_solicitud->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </td>
            <td width="50%" style="vertical-align: top;">
                <div class="info-grid">
                    @if($desembolso->estado === 'pagado')
                        <div class="info-row">
                            <span class="info-label">N° Transferencia:</span>
                            <span class="info-value font-bold">{{ $desembolso->numero_transferencia }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Banco:</span>
                            <span class="info-value">{{ $desembolso->banco }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Fecha Pago:</span>
                            <span class="info-value">{{ $desembolso->fecha_pago?->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Procesado por:</span>
                            <span class="info-value">{{ $desembolso->procesador?->name ?? 'N/A' }}</span>
                        </div>
                    @elseif($desembolso->estado === 'rechazado')
                        <div class="info-row">
                            <span class="info-label">Rechazado por:</span>
                            <span class="info-value">{{ $desembolso->procesador?->name ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Fecha Rechazo:</span>
                            <span class="info-value">{{ $desembolso->fecha_proceso?->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

@if($desembolso->estado === 'rechazado' && $desembolso->motivo_rechazo)
    <div class="seccion">
        <div class="seccion-titulo" style="color: #dc2626;">Motivo de Rechazo</div>
        <div style="background: #fee2e2; padding: 10px; border-radius: 5px; color: #991b1b;">
            {{ $desembolso->motivo_rechazo }}
        </div>
    </div>
@endif

<!-- Desglose por Línea Presupuestaria -->
<div class="seccion">
    <div class="seccion-titulo">Desglose por Línea Presupuestaria</div>
    <table class="tabla-datos">
        <thead>
        <tr>
            <th>Línea Presupuestaria</th>
            <th class="text-right">Planificado</th>
            <th class="text-right">Ejecutado</th>
            <th class="text-center">% Ejecución</th>
        </tr>
        </thead>
        <tbody>
        @php $totalPlanificado = 0; $totalEjecutado = 0; @endphp
        @forelse($desembolso->desglose_lineas ?? [] as $linea)
            @php
                $porcentaje = $linea['planificado'] > 0 ? round(($linea['ejecutado'] / $linea['planificado']) * 100) : 0;
                $totalPlanificado += $linea['planificado'];
                $totalEjecutado += $linea['ejecutado'];
            @endphp
            <tr>
                <td>{{ $linea['linea'] }}</td>
                <td class="text-right">${{ number_format($linea['planificado'], 2) }}</td>
                <td class="text-right">${{ number_format($linea['ejecutado'], 2) }}</td>
                <td class="text-center">
                    <span class="{{ $porcentaje >= 100 ? 'text-green' : 'text-blue' }}">{{ $porcentaje }}%</span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center">Sin desglose disponible</td>
            </tr>
        @endforelse
        </tbody>
        <tfoot>
        <tr style="background: #e5e7eb; font-weight: bold;">
            <td>TOTAL</td>
            <td class="text-right">${{ number_format($totalPlanificado, 2) }}</td>
            <td class="text-right">${{ number_format($totalEjecutado, 2) }}</td>
            <td class="text-center">
                {{ $totalPlanificado > 0 ? round(($totalEjecutado / $totalPlanificado) * 100) : 0 }}%
            </td>
        </tr>
        </tfoot>
    </table>
</div>

<!-- Actividades del Producto -->
<div class="seccion">
    <div class="seccion-titulo">Actividades del Producto ({{ $desembolso->producto->actividades->count() }})</div>
    <table class="tabla-datos">
        <thead>
        <tr>
            <th width="35%">Actividad</th>
            <th>Línea</th>
            <th class="text-right">Monto</th>
            <th class="text-right">Ejecutado</th>
            <th class="text-center">Progreso</th>
            <th class="text-center">Estado</th>
        </tr>
        </thead>
        <tbody>
        @foreach($desembolso->producto->actividades as $actividad)
            <tr>
                <td>{{ Str::limit($actividad->nombre, 40) }}</td>
                <td>{{ Str::limit($actividad->linea_presupuestaria, 15) }}</td>
                <td class="text-right">${{ number_format($actividad->monto, 2) }}</td>
                <td class="text-right">${{ number_format($actividad->gasto_acumulado, 2) }}</td>
                <td class="text-center">{{ $actividad->progreso }}%</td>
                <td class="text-center">
                    @php
                        $estadoColor = match($actividad->estado) {
                            'finalizado' => 'text-green',
                            'en_curso' => 'text-blue',
                            'atrasado' => 'text-red',
                            default => '',
                        };
                    @endphp
                    <span class="{{ $estadoColor }}">{{ ucfirst(str_replace('_', ' ', $actividad->estado)) }}</span>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@if($desembolso->observaciones)
    <div class="seccion">
        <div class="seccion-titulo">Observaciones</div>
        <div style="background: #f9fafb; padding: 10px; border-radius: 5px;">
            {{ $desembolso->observaciones }}
        </div>
    </div>
@endif

<!-- Footer -->
<div class="footer">
    <p>FAO Bolivia - Sistema de Gestión de Cartas Documento</p>
    <p>Este documento fue generado automáticamente el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i') }}</p>
</div>
</body>
</html>
