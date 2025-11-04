<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class ReporteComparativoExport implements
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
        return $this->datos->map(function ($carta, $index) {
            return [
                'N°' => $index + 1,
                'Código' => $carta['codigo'],
                'Proyecto' => $carta['nombre'],
                'Presupuesto' => $carta['presupuesto'],
                'Ejecutado' => $carta['ejecutado'],
                'Eficiencia' => $carta['eficiencia'] / 100,
                'Progreso' => $carta['progreso'] / 100,
                'Total Actividades' => $carta['actividades_total'],
                'Actividades Completadas' => $carta['actividades_completadas'],
                '% Completitud' => $carta['actividades_total'] > 0
                    ? $carta['actividades_completadas'] / $carta['actividades_total']
                    : 0,
                'Productos' => $carta['productos_count'],
                'Duración (días)' => $carta['duracion_dias'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'N°',
            'Código',
            'Proyecto',
            'Presupuesto (USD)',
            'Ejecutado (USD)',
            'Eficiencia',
            'Progreso',
            'Total Actividades',
            'Actividades Completadas',
            '% Completitud',
            'Productos',
            'Duración (días)'
        ];
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
                    'startColor' => ['rgb' => '9C27B0'] // Color morado
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // N°
            'B' => 18,  // Código
            'C' => 40,  // Proyecto
            'D' => 18,  // Presupuesto
            'E' => 18,  // Ejecutado
            'F' => 12,  // Eficiencia
            'G' => 12,  // Progreso
            'H' => 16,  // Total Actividades
            'I' => 20,  // Actividades Completadas
            'J' => 14,  // % Completitud
            'K' => 12,  // Productos
            'L' => 16,  // Duración
        ];
    }

    public function title(): string
    {
        return 'Comparativo de Cartas';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Bordes
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ]);

                // Formato de moneda
                $sheet->getStyle('D2:E' . $highestRow)->getNumberFormat()
                    ->setFormatCode('$#,##0.00');

                // Formato de porcentaje
                $sheet->getStyle('F2:G' . $highestRow)->getNumberFormat()
                    ->setFormatCode('0.00%');
                $sheet->getStyle('J2:J' . $highestRow)->getNumberFormat()
                    ->setFormatCode('0.00%');

                // Centrar
                $sheet->getStyle('A2:A' . $highestRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F2:L' . $highestRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Colores alternos
                for ($i = 2; $i <= $highestRow; $i++) {
                    if ($i % 2 == 0) {
                        $sheet->getStyle('A' . $i . ':' . $highestColumn . $i)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setRGB('F3E5F5');
                    }
                }

                // Colorear eficiencia
                for ($i = 2; $i <= $highestRow; $i++) {
                    $eficienciaValue = $sheet->getCell('F' . $i)->getValue();
                    if (is_numeric($eficienciaValue)) {
                        if ($eficienciaValue > 1) {
                            // Rojo si supera el 100% (sobrepresupuesto)
                            $sheet->getStyle('F' . $i)->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setRGB('FFCDD2');
                        } elseif ($eficienciaValue >= 0.8 && $eficienciaValue <= 1) {
                            // Verde si está entre 80-100%
                            $sheet->getStyle('F' . $i)->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setRGB('C8E6C9');
                        }
                    }
                }

                // Título
                $sheet->insertNewRowBefore(1, 3);
                $sheet->mergeCells('A1:' . $highestColumn . '1');
                $sheet->setCellValue('A1', 'REPORTE COMPARATIVO DE CARTAS - FAO GESTIÓN');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => '9C27B0']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Fecha
                $sheet->mergeCells('A2:' . $highestColumn . '2');
                $sheet->setCellValue('A2', 'Generado el: ' . now()->format('d/m/Y H:i:s'));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'size' => 10,
                        'italic' => true,
                        'color' => ['rgb' => '666666']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Totales/Promedios
                $lastRow = $highestRow + 1;
                $sheet->setCellValue('A' . $lastRow, '');
                $sheet->setCellValue('B' . $lastRow, '');
                $sheet->setCellValue('C' . $lastRow, 'TOTALES/PROMEDIOS:');
                $sheet->setCellValue('D' . $lastRow, '=SUM(D4:D' . ($highestRow) . ')');
                $sheet->setCellValue('E' . $lastRow, '=SUM(E4:E' . ($highestRow) . ')');
                $sheet->setCellValue('F' . $lastRow, '=AVERAGE(F4:F' . ($highestRow) . ')');
                $sheet->setCellValue('G' . $lastRow, '=AVERAGE(G4:G' . ($highestRow) . ')');
                $sheet->setCellValue('H' . $lastRow, '=SUM(H4:H' . ($highestRow) . ')');
                $sheet->setCellValue('I' . $lastRow, '=SUM(I4:I' . ($highestRow) . ')');
                $sheet->setCellValue('J' . $lastRow, '=AVERAGE(J4:J' . ($highestRow) . ')');
                $sheet->setCellValue('K' . $lastRow, '=SUM(K4:K' . ($highestRow) . ')');
                $sheet->setCellValue('L' . $lastRow, '=AVERAGE(L4:L' . ($highestRow) . ')');

                $sheet->getStyle('A' . $lastRow . ':' . $highestColumn . $lastRow)
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 11,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E1BEE7']
                        ],
                        'borders' => [
                            'top' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                                'color' => ['rgb' => '9C27B0'],
                            ],
                        ],
                    ]);

                $sheet->getStyle('D' . $lastRow . ':E' . $lastRow)
                    ->getNumberFormat()
                    ->setFormatCode('$#,##0.00');

                $sheet->getStyle('F' . $lastRow . ':G' . $lastRow)
                    ->getNumberFormat()
                    ->setFormatCode('0.00%');

                $sheet->getStyle('J' . $lastRow)
                    ->getNumberFormat()
                    ->setFormatCode('0.00%');

                // Altura de filas
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(25);
            },
        ];
    }
}
