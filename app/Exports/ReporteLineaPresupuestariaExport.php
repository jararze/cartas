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

class ReporteLineaPresupuestariaExport implements
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
        // Si datos es una colección, convertir a array
        $lineas = is_array($this->datos) ? collect($this->datos) : $this->datos;

        return $lineas->map(function ($linea, $index) {
            return [
                'N°' => $index + 1,
                'Línea Presupuestaria' => $linea['linea_presupuestaria'] ?? $linea['nombre'] ?? 'N/A',
                'Presupuesto' => $linea['presupuesto'] ?? $linea['total_presupuesto'] ?? 0,
                'Ejecutado' => $linea['ejecutado'] ?? $linea['total_ejecutado'] ?? 0,
                'Saldo' => $linea['saldo'] ?? 0,
                '% Ejecución' => ($linea['porcentaje_ejecucion'] ?? 0) / 100,
                'Actividades' => $linea['actividades_count'] ?? $linea['total_actividades'] ?? 0,
                'Cartas' => $linea['cartas_count'] ?? $linea['total_cartas'] ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'N°',
            'Línea Presupuestaria',
            'Presupuesto (USD)',
            'Ejecutado (USD)',
            'Saldo (USD)',
            '% Ejecución',
            'Actividades',
            'Cartas'
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
                    'startColor' => ['rgb' => '4CAF50'] // Color verde
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
            'B' => 30,  // Línea Presupuestaria
            'C' => 18,  // Presupuesto
            'D' => 18,  // Ejecutado
            'E' => 18,  // Saldo
            'F' => 14,  // % Ejecución
            'G' => 14,  // Actividades
            'H' => 12,  // Cartas
        ];
    }

    public function title(): string
    {
        return 'Líneas Presupuestarias';
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
                $sheet->getStyle('C2:E' . $highestRow)->getNumberFormat()
                    ->setFormatCode('$#,##0.00');

                // Formato de porcentaje
                $sheet->getStyle('F2:F' . $highestRow)->getNumberFormat()
                    ->setFormatCode('0.00%');

                // Centrar columnas
                $sheet->getStyle('A2:A' . $highestRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F2:H' . $highestRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Colores alternos
                for ($i = 2; $i <= $highestRow; $i++) {
                    if ($i % 2 == 0) {
                        $sheet->getStyle('A' . $i . ':' . $highestColumn . $i)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setRGB('E8F5E9');
                    }
                }

                // Título
                $sheet->insertNewRowBefore(1, 3);
                $sheet->mergeCells('A1:' . $highestColumn . '1');
                $sheet->setCellValue('A1', 'ANÁLISIS POR LÍNEA PRESUPUESTARIA - FAO GESTIÓN');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => '4CAF50']
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

                // Totales
                $lastRow = $highestRow + 1;
                $sheet->setCellValue('A' . $lastRow, '');
                $sheet->setCellValue('B' . $lastRow, 'TOTALES:');
                $sheet->setCellValue('C' . $lastRow, '=SUM(C4:C' . ($highestRow) . ')');
                $sheet->setCellValue('D' . $lastRow, '=SUM(D4:D' . ($highestRow) . ')');
                $sheet->setCellValue('E' . $lastRow, '=SUM(E4:E' . ($highestRow) . ')');
                $sheet->setCellValue('F' . $lastRow, '=AVERAGE(F4:F' . ($highestRow) . ')');
                $sheet->setCellValue('G' . $lastRow, '=SUM(G4:G' . ($highestRow) . ')');
                $sheet->setCellValue('H' . $lastRow, '=SUM(H4:H' . ($highestRow) . ')');

                $sheet->getStyle('A' . $lastRow . ':' . $highestColumn . $lastRow)
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 11,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'C8E6C9']
                        ],
                        'borders' => [
                            'top' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                                'color' => ['rgb' => '4CAF50'],
                            ],
                        ],
                    ]);

                $sheet->getStyle('C' . $lastRow . ':E' . $lastRow)
                    ->getNumberFormat()
                    ->setFormatCode('$#,##0.00');

                $sheet->getStyle('F' . $lastRow)
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
