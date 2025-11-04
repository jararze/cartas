<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReporteFinancieroExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithTitle,
    WithEvents
{
    protected $datos;
    protected $fechaInicio;
    protected $fechaFin;

    public function __construct($datos, $fechaInicio = null, $fechaFin = null)
    {
        $this->datos = $datos;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }

    /**
     * Retorna la colección de datos
     */
    public function collection()
    {
        return $this->datos->map(function ($carta, $index) {
            return [
                'N°' => $index + 1,
                'Código' => $carta['codigo'],
                'Proyecto' => $carta['nombre_proyecto'],
                'Presupuesto' => $carta['presupuesto_total'],
                'Ejecutado' => $carta['ejecutado_total'],
                'Saldo' => $carta['saldo'],
                '% Ejecución' => $carta['porcentaje_ejecucion'] / 100,
                '% Progreso' => $carta['progreso_promedio'] / 100,
                'Productos' => $carta['productos_count'],
                'Actividades' => $carta['actividades_count'],
                'Fecha Inicio' => $carta['fecha_inicio'],
                'Fecha Fin' => $carta['fecha_fin'],
                'Estado' => ucfirst(str_replace('_', ' ', $carta['estado'])),
            ];
        });
    }

    /**
     * Encabezados de las columnas
     */
    public function headings(): array
    {
        return [
            'N°',
            'Código',
            'Proyecto',
            'Presupuesto (USD)',
            'Ejecutado (USD)',
            'Saldo (USD)',
            '% Ejecución',
            '% Progreso',
            'Productos',
            'Actividades',
            'Fecha Inicio',
            'Fecha Fin',
            'Estado'
        ];
    }

    /**
     * Estilos para las celdas
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para el encabezado
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
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Ancho de las columnas
     */
    public function columnWidths(): array
    {
        return [
            'A' => 6,   // N°
            'B' => 18,  // Código
            'C' => 40,  // Proyecto
            'D' => 18,  // Presupuesto
            'E' => 18,  // Ejecutado
            'F' => 18,  // Saldo
            'G' => 14,  // % Ejecución
            'H' => 14,  // % Progreso
            'I' => 12,  // Productos
            'J' => 14,  // Actividades
            'K' => 15,  // Fecha Inicio
            'L' => 15,  // Fecha Fin
            'M' => 18,  // Estado
        ];
    }

    /**
     * Título de la hoja
     */
    public function title(): string
    {
        return 'Reporte Financiero';
    }

    /**
     * Eventos adicionales
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Aplicar bordes a toda la tabla
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ]);

                // Formato de moneda para columnas D, E, F
                $sheet->getStyle('D2:F' . $highestRow)->getNumberFormat()
                    ->setFormatCode('$#,##0.00');

                // Formato de porcentaje para columnas G, H
                $sheet->getStyle('G2:H' . $highestRow)->getNumberFormat()
                    ->setFormatCode('0.00%');

                // Centrar columnas numéricas
                $sheet->getStyle('A2:A' . $highestRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('G2:J' . $highestRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Alinear fechas al centro
                $sheet->getStyle('K2:L' . $highestRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Formato de fecha
                $sheet->getStyle('K2:L' . $highestRow)->getNumberFormat()
                    ->setFormatCode('dd/mm/yyyy');

                // Aplicar colores alternos a las filas
                for ($i = 2; $i <= $highestRow; $i++) {
                    if ($i % 2 == 0) {
                        $sheet->getStyle('A' . $i . ':' . $highestColumn . $i)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setRGB('F8F9FA');
                    }
                }

                // Agregar título del reporte
                $sheet->insertNewRowBefore(1, 3);
                $sheet->mergeCells('A1:' . $highestColumn . '1');
                $sheet->setCellValue('A1', 'REPORTE FINANCIERO - FAO GESTIÓN');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => '0073E6']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Agregar fecha de generación
                $sheet->mergeCells('A2:' . $highestColumn . '2');
                $fechaTexto = 'Generado el: ' . now()->format('d/m/Y H:i:s');
                if ($this->fechaInicio && $this->fechaFin) {
                    $fechaTexto .= ' | Período: ' . $this->fechaInicio . ' - ' . $this->fechaFin;
                }
                $sheet->setCellValue('A2', $fechaTexto);
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

                // Agregar fila de totales al final
                $lastRow = $highestRow + 1;
                $sheet->setCellValue('A' . $lastRow, '');
                $sheet->setCellValue('B' . $lastRow, '');
                $sheet->setCellValue('C' . $lastRow, 'TOTALES:');
                $sheet->setCellValue('D' . $lastRow, '=SUM(D4:D' . ($highestRow) . ')');
                $sheet->setCellValue('E' . $lastRow, '=SUM(E4:E' . ($highestRow) . ')');
                $sheet->setCellValue('F' . $lastRow, '=SUM(F4:F' . ($highestRow) . ')');
                $sheet->setCellValue('G' . $lastRow, '=AVERAGE(G4:G' . ($highestRow) . ')');
                $sheet->setCellValue('H' . $lastRow, '=AVERAGE(H4:H' . ($highestRow) . ')');
                $sheet->setCellValue('I' . $lastRow, '=SUM(I4:I' . ($highestRow) . ')');
                $sheet->setCellValue('J' . $lastRow, '=SUM(J4:J' . ($highestRow) . ')');

                // Estilo para la fila de totales
                $sheet->getStyle('A' . $lastRow . ':' . $highestColumn . $lastRow)
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 11,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E3F2FD']
                        ],
                        'borders' => [
                            'top' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                                'color' => ['rgb' => '0073E6'],
                            ],
                        ],
                    ]);

                // Formato de moneda para totales
                $sheet->getStyle('D' . $lastRow . ':F' . $lastRow)
                    ->getNumberFormat()
                    ->setFormatCode('$#,##0.00');

                // Formato de porcentaje para totales
                $sheet->getStyle('G' . $lastRow . ':H' . $lastRow)
                    ->getNumberFormat()
                    ->setFormatCode('0.00%');

                // Ajustar altura de filas
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(25);
            },
        ];
    }
}
