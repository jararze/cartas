
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Avance - FAO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .header { background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); color: white; padding: 20px; text-align: center; margin-bottom: 20px; border-radius: 5px; }
        .header h1 { font-size: 22px; margin-bottom: 5px; }
        .header p { font-size: 11px; opacity: 0.9; }
        .info-section { background: #f8f9fa; padding: 12px; margin-bottom: 15px; border-left: 4px solid #7c3aed; border-radius: 3px; }
        .info-section p { margin: 3px 0; font-size: 10px; }
        .info-section strong { color: #7c3aed; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 9px; }
        table thead { background: #7c3aed; color: white; }
        table thead th { padding: 10px 5px; text-align: left; font-weight: 600; font-size: 9px; border: 1px solid #5b21b6; }
        table tbody td { padding: 8px 5px; border: 1px solid #dee2e6; }
        table tbody tr:nth-child(even) { background: #f8f9fa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 8px; font-weight: 600; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .totales { background: #ede9fe; font-weight: 700; border-top: 3px solid #7c3aed; }
        .totales td { padding: 10px 5px; font-size: 10px; }
        .footer { text-align: center; font-size: 8px; color: #6c757d; margin-top: 30px; padding-top: 15px; border-top: 1px solid #dee2e6; }
        .progress-bar { width: 100%; height: 12px; background: #e9ecef; border-radius: 6px; overflow: hidden; display: inline-block; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #7c3aed 0%, #a78bfa 100%); border-radius: 6px; text-align: center; color: white; font-size: 8px; line-height: 12px; }
        .stats-grid { display: table; width: 100%; margin-bottom: 20px; }
        .stats-item { display: table-cell; width: 25%; padding: 10px; text-align: center; background: #f8f9fa; border: 1px solid #dee2e6; }
        .stats-item .value { font-size: 18px; font-weight: 700; color: #7c3aed; display: block; margin: 5px 0; }
        .stats-item .label { font-size: 9px; color: #6c757d; }
    </style>
</head>
<body>
<div class="header">
    <h1>📈 REPORTE DE AVANCE</h1>
    <p>Sistema de Gestión FAO - Progreso de Actividades</p>
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
        <div class="label">Progreso Promedio</div>
        <div class="value">{{ number_format($datos->avg('progreso_general'), 1) }}%</div>
    </div>
    <div class="stats-item">
        <div class="label">Actividades Totales</div>
        <div class="value">{{ $datos->sum('total_actividades') }}</div>
    </div>
    <div class="stats-item">
        <div class="label">Completadas</div>
        <div class="value">{{ $datos->sum('completadas') }}</div>
    </div>
    <div class="stats-item">
        <div class="label">En Curso</div>
        <div class="value">{{ $datos->sum('en_curso') }}</div>
    </div>
</div>

<table>
    <thead>
    <tr>
        <th>N°</th>
        <th>Código</th>
        <th>Proyecto</th>
        <th class="text-center">Progreso</th>
        <th class="text-center">Total</th>
        <th class="text-center">Completadas</th>
        <th class="text-center">En Curso</th>
        <th class="text-center">Pendientes</th>
        <th class="text-center">Atrasadas</th>
    </tr>
    </thead>
    <tbody>
    @foreach($datos as $index => $item)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td>{{ $item['codigo'] }}</td>
            <td>{{ $item['nombre_proyecto'] }}</td>
            <td class="text-center">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $item['progreso_general'] }}%">
                        {{ number_format($item['progreso_general'], 0) }}%
                    </div>
                </div>
            </td>
            <td class="text-center">{{ $item['total_actividades'] }}</td>
            <td class="text-center">
                <span class="badge badge-success">{{ $item['completadas'] }}</span>
            </td>
            <td class="text-center">
                <span class="badge badge-info">{{ $item['en_curso'] }}</span>
            </td>
            <td class="text-center">
                <span class="badge badge-warning">{{ $item['pendientes'] }}</span>
            </td>
            <td class="text-center">
                <span class="badge badge-danger">{{ $item['atrasadas'] }}</span>
            </td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="totales">
        <td colspan="4" class="text-right"><strong>TOTALES:</strong></td>
        <td class="text-center"><strong>{{ $datos->sum('total_actividades') }}</strong></td>
        <td class="text-center"><strong>{{ $datos->sum('completadas') }}</strong></td>
        <td class="text-center"><strong>{{ $datos->sum('en_curso') }}</strong></td>
        <td class="text-center"><strong>{{ $datos->sum('pendientes') }}</strong></td>
        <td class="text-center"><strong>{{ $datos->sum('atrasadas') }}</strong></td>
    </tr>
    </tfoot>
</table>

<div class="footer">
    <p>Este reporte fue generado automáticamente por el Sistema de Gestión FAO</p>
    <p>© {{ date('Y') }} FAO - Organización de las Naciones Unidas para la Alimentación y la Agricultura</p>
</div>
</body>
</html>
