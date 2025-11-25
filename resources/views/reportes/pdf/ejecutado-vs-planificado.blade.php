<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ejecutado vs Planificado - FAO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .header { background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; padding: 20px; text-align: center; margin-bottom: 20px; border-radius: 5px; }
        .header h1 { font-size: 22px; margin-bottom: 5px; }
        .header p { font-size: 11px; opacity: 0.9; }
        .info-section { background: #f8f9fa; padding: 12px; margin-bottom: 15px; border-left: 4px solid #dc2626; border-radius: 3px; }
        .info-section p { margin: 3px 0; font-size: 10px; }
        .info-section strong { color: #dc2626; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 9px; }
        table thead { background: #dc2626; color: white; }
        table thead th { padding: 10px 5px; text-align: left; font-weight: 600; font-size: 9px; border: 1px solid #991b1b; }
        table tbody td { padding: 8px 5px; border: 1px solid #dee2e6; }
        table tbody tr:nth-child(even) { background: #f8f9fa; }
        table tbody tr:hover { background: #fee2e2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 8px; font-weight: 600; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .totales { background: #fee2e2; font-weight: 700; border-top: 3px solid #dc2626; }
        .totales td { padding: 10px 5px; font-size: 10px; }
        .footer { text-align: center; font-size: 8px; color: #6c757d; margin-top: 30px; padding-top: 15px; border-top: 1px solid #dee2e6; }
        .stats-grid { display: table; width: 100%; margin-bottom: 20px; }
        .stats-item { display: table-cell; width: 25%; padding: 10px; text-align: center; background: #f8f9fa; border: 1px solid #dee2e6; }
        .stats-item .value { font-size: 18px; font-weight: 700; color: #dc2626; display: block; margin: 5px 0; }
        .stats-item .label { font-size: 9px; color: #6c757d; }
        .text-positive { color: #059669; font-weight: bold; }
        .text-negative { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
<div class="header">
    <h1>📊 EJECUTADO VS PLANIFICADO</h1>
    <p>Sistema de Gestión FAO - Análisis Comparativo Presupuestal</p>
</div>

<div class="info-section">
    <p><strong>Fecha de Generación:</strong> {{ $fechaGeneracion }}</p>
    @if($fechaInicio && $fechaFin)
        <p><strong>Período Analizado:</strong> {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</p>
    @endif
    <p><strong>Total de Cartas:</strong> {{ $datos->count() }}</p>
</div>

<div class="stats-grid">
    <div class="stats-item">
        <div class="label">Planificado Total</div>
        <div class="value">${{ number_format($datos->sum('presupuesto_planificado'), 2) }}</div>
    </div>
    <div class="stats-item">
        <div class="label">Ejecutado Total</div>
        <div class="value">${{ number_format($datos->sum('presupuesto_ejecutado'), 2) }}</div>
    </div>
    <div class="stats-item">
        <div class="label">Variación Total</div>
        <div class="value" style="color: {{ $datos->sum('variacion') > 0 ? '#dc2626' : '#059669' }}">
            ${{ number_format($datos->sum('variacion'), 2) }}
        </div>
    </div>
    <div class="stats-item">
        <div class="label">% Variación Promedio</div>
        <div class="value">{{ number_format($datos->avg('variacion_porcentaje'), 1) }}%</div>
    </div>
</div>

<table>
    <thead>
    <tr>
        <th>N°</th>
        <th>Código</th>
        <th>Proyecto</th>
        <th class="text-right">Planificado</th>
        <th class="text-right">Ejecutado</th>
        <th class="text-right">Variación</th>
        <th class="text-center">% Variación</th>
        <th class="text-center">Estado</th>
    </tr>
    </thead>
    <tbody>
    @foreach($datos as $index => $item)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td>{{ $item['carta_codigo'] }}</td>
            <td>{{ $item['nombre_proyecto'] }}</td>
            <td class="text-right">${{ number_format($item['presupuesto_planificado'], 2) }}</td>
            <td class="text-right">${{ number_format($item['presupuesto_ejecutado'], 2) }}</td>
            <td class="text-right {{ $item['variacion'] > 0 ? 'text-negative' : 'text-positive' }}">
                ${{ number_format($item['variacion'], 2) }}
            </td>
            <td class="text-center {{ $item['variacion_porcentaje'] > 0 ? 'text-negative' : 'text-positive' }}">
                {{ number_format($item['variacion_porcentaje'], 1) }}%
            </td>
            <td class="text-center">
                @if($item['variacion'] > 0)
                    <span class="badge badge-danger">Sobre presupuesto</span>
                @elseif($item['variacion'] < 0)
                    <span class="badge badge-success">Bajo presupuesto</span>
                @else
                    <span class="badge badge-warning">En presupuesto</span>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="totales">
        <td colspan="3" class="text-right"><strong>TOTALES:</strong></td>
        <td class="text-right"><strong>${{ number_format($datos->sum('presupuesto_planificado'), 2) }}</strong></td>
        <td class="text-right"><strong>${{ number_format($datos->sum('presupuesto_ejecutado'), 2) }}</strong></td>
        <td class="text-right {{ $datos->sum('variacion') > 0 ? 'text-negative' : 'text-positive' }}">
            <strong>${{ number_format($datos->sum('variacion'), 2) }}</strong>
        </td>
        <td class="text-center"><strong>{{ number_format($datos->avg('variacion_porcentaje'), 1) }}%</strong></td>
        <td></td>
    </tr>
    </tfoot>
</table>

<div class="footer">
    <p>Este reporte fue generado automáticamente por el Sistema de Gestión FAO</p>
    <p>© {{ date('Y') }} FAO - Organización de las Naciones Unidas para la Alimentación y la Agricultura</p>
</div>
</body>
</html>
