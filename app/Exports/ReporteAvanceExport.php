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
use PhpOffice\PhpSpreadsheet\Style\Color;

class ReporteAvanceExport implements
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
        return $this->datos->map(function ($actividad, $index) {
            return [
                'N°' => $index + 1,
                'Carta' => $actividad['carta'],
                'Producto' => $actividad['producto'],
                'Actividad' => $actividad['nombre'],
                'Línea Presupuestaria' => $actividad['linea_presupuestaria'],
                'Presupuesto' => $actividad['presupuesto'],
                'Ejecutado' => $actividad['ejecutado'],
                'Saldo' => $actividad['saldo'],
                'Progreso Real' => $actividad['progreso_real'] / 100,
                'Avance Esperado' => $actividad['avance_esperado'] / 100,
                'Desviación' => $actividad['desviacion'] / 100,
                'Estado' => ucfirst(str_replace('_', ' ', $actividad['estado'])),
                'Fecha Inicio' => $actividad['fecha_inicio'],
                'Fecha Fin' => $actividad['fecha_fin'],
                'Responsable' => $actividad['responsable'],
                'Seguimientos' => $actividad['seguimientos_count'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'N°',
            'Carta',
            'Producto',
            'Actividad',
            'Línea Presupuestaria',
            'Presupuesto (USD)',
            'Ejecutado (USD)',
            'Saldo (USD)',
            'Progreso Real',
            'Avance Esperado',
            'Desviación',
            'Estado',
            'Fecha Inicio',
            'Fecha Fin',
            'Responsable',
            'N° Seguimientos'
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
                    'startColor' => ['rgb' => 'FF6B35'] // Color naranja
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
            'B' => 18,  // Carta
            'C' => 25,  // Producto
            'D' => 35,  // Actividad
            'E' => 20,  // Línea Presupuestaria
            'F' => 16,  // Presupuesto
            'G' => 16,  // Ejecutado
            'H' => 16,  // Saldo
            'I' => 14,  // Progreso Real
            'J' => 16,  // Avance Esperado
            'K' => 12,  // Desviación
            'L' => 15,  // Estado
            'M' => 14,  // Fecha Inicio
            'N' => 14,  // Fecha Fin
            'O' => 20,  // Responsable
            'P' => 14,  // Seguimientos
        ];
    }

    public function title(): string
    {
        return 'Reporte de Avances';
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
                $sheet->getStyle('F2:H' . $highestRow)->getNumberFormat()
                    ->setFormatCode('$#,##0.00');

                // Formato de porcentaje
                $sheet->getStyle('I2:K' . $highestRow)->getNumberFormat()
                    ->setFormatCode('0.00%');

                // Centrar columnas
                $sheet->getStyle('A2:A' . $highestRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('I2:L' . $highestRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('M2:N' . $highestRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('P2:P' . $highestRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Formato de fecha
                $sheet->getStyle('M2:N' . $highestRow)->getNumberFormat()
                    ->setFormatCode('dd/mm/yyyy');

                // Colores alternos
                for ($i = 2; $i <= $highestRow; $i++) {
                    if ($i % 2 == 0) {
                        $sheet->getStyle('A' . $i . ':' . $highestColumn . $i)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setRGB('FFF8F0');
                    }
                }

                // Colorear desviaciones negativas en rojo
                for ($i = 2; $i <= $highestRow; $i++) {
                    $desviacionValue = $sheet->getCell('K' . $i)->getValue();
                    if (is_numeric($desviacionValue) && $desviacionValue < 0) {
                        $sheet->getStyle('K' . $i)->getFont()->getColor()->setRGB('DC3545');
                        $sheet->getStyle('K' . $i)->getFont()->setBold(true);
                    }
                }

                // Título
                $sheet->insertNewRowBefore(1, 3);
                $sheet->mergeCells('A1:' . $highestColumn . '1');
                $sheet->setCellValue('A1', 'REPORTE DE AVANCE DE ACTIVIDADES - FAO GESTIÓN');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => 'FF6B35']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Fecha de generación
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
                $sheet->setCellValue('B' . $lastRow, '');
                $sheet->setCellValue('C' . $lastRow, '');
                $sheet->setCellValue('D' . $lastRow, '');
                $sheet->setCellValue('E' . $lastRow, 'TOTALES:');
                $sheet->setCellValue('F' . $lastRow, '=SUM(F4:F' . ($highestRow) . ')');
                $sheet->setCellValue('G' . $lastRow, '=SUM(G4:G' . ($highestRow) . ')');
                $sheet->setCellValue('H' . $lastRow, '=SUM(H4:H' . ($highestRow) . ')');
                $sheet->setCellValue('I' . $lastRow, '=AVERAGE(I4:I' . ($highestRow) . ')');
                $sheet->setCellValue('J' . $lastRow, '=AVERAGE(J4:J' . ($highestRow) . ')');
                $sheet->setCellValue('K' . $lastRow, '=AVERAGE(K4:K' . ($highestRow) . ')');

                $sheet->getStyle('A' . $lastRow . ':' . $highestColumn . $lastRow)
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 11,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFE5D9']
                        ],
                        'borders' => [
                            'top' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                                'color' => ['rgb' => 'FF6B35'],
                            ],
                        ],
                    ]);

                $sheet->getStyle('F' . $lastRow . ':H' . $lastRow)
                    ->getNumberFormat()
                    ->setFormatCode('$#,##0.00');

                $sheet->getStyle('I' . $lastRow . ':K' . $lastRow)
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
