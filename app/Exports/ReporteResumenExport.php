<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReporteResumenExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithTitle,
    WithEvents
{
    protected $datos;

    public function __construct($datos)
    {
        $this->datos = $datos;
    }

    public function collection()
    {
        // Verificar estructura de datos
        if (!isset($this->datos['estadisticas'])) {
            \Log::warning('No se encontró la clave estadisticas en los datos');
            return collect([]);
        }

        $estadisticas = $this->datos['estadisticas'];

        // Función auxiliar para obtener valores de forma segura
        $get = function($key, $default = 0) use ($estadisticas) {
            return $estadisticas[$key] ?? $default;
        };

        return collect([
            ['Métrica', 'Valor'],
            ['Total Cartas', $get('total_cartas')],
            ['Cartas Activas', $get('cartas_activas')],
            ['Cartas Finalizadas', $get('cartas_finalizadas')],
            ['Presupuesto Total', $get('total_presupuesto', 0)],
            ['Ejecutado Total', $get('total_ejecutado', 0)],
            ['Saldo Disponible', $get('saldo_disponible', 0)],
            ['% Ejecución Presupuestaria', number_format($get('ejecucion_presupuestaria', 0), 2) . '%'],
            ['Total Actividades', $get('actividades_total')],
            ['Actividades Completadas', $get('actividades_completadas')],
            ['Actividades En Curso', $get('actividades_en_curso')],
            ['Actividades Pendientes', $get('actividades_pendientes')],
            ['Actividades Atrasadas', $get('actividades_atrasadas')],
            ['% Progreso Promedio', number_format($get('progreso_promedio', 0), 2) . '%'],
        ]);
    }

    public function headings(): array
    {
        return []; // Los encabezados ya están en la colección
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0073E6']
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 25,
        ];
    }

    public function title(): string
    {
        return 'Resumen Ejecutivo';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();

                // Bordes
                $sheet->getStyle('A1:B' . $highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ]);

                // Formato de moneda para filas específicas
                $sheet->getStyle('B3:B5')->getNumberFormat()
                    ->setFormatCode('$#,##0.00');

                // Centrar columna B
                $sheet->getStyle('B2:B' . $highestRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Título
                $sheet->insertNewRowBefore(1, 2);
                $sheet->mergeCells('A1:B1');
                $sheet->setCellValue('A1', 'RESUMEN EJECUTIVO - FAO GESTIÓN');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => '0073E6']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(30);
            },
        ];
    }
}
