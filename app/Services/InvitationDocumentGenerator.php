<?php

namespace App\Services;

use App\Models\Carta;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Paragraph;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;
use Carbon\Carbon;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * GENERADOR DE DOCUMENTOS WORD - ADAPTADO A TU ESTRUCTURA
 * ════════════════════════════════════════════════════════════════════════════
 *
 * Este generador está adaptado a tu estructura de base de datos específica:
 * - nombre_proyecto (en lugar de project_name)
 * - descripcion_servicios (en lugar de service_description)
 * - antecedentes (en lugar de background)
 * - servicios_requeridos (en lugar de required_services)
 * - productos_requeridos (en lugar de required_products)
 * - fecha_inicio, fecha_fin
 * - monto_total, moneda
 * - codigo (campo único de tu sistema)
 */
class InvitationDocumentGenerator
{
    protected $phpWord;
    protected $section;
    protected $carta;

    public function __construct(Carta $carta)
    {
        $this->carta = $carta;
        $this->phpWord = new PhpWord();
        $this->setupDocument();
    }

    /**
     * Configuración inicial del documento
     */
    protected function setupDocument()
    {
        // Configurar propiedades del documento
        $properties = $this->phpWord->getDocInfo();
        $properties->setCreator('FAO Bolivia - Sistema de Cartas');
        $properties->setTitle('Invitación de Propuesta - ' . $this->carta->codigo);
        $properties->setDescription('Documento generado automáticamente para ' . $this->carta->nombre_proyecto);
        $properties->setCategory('Invitación de Propuesta');
        $properties->setSubject('Solicitud de Propuestas - ' . $this->carta->codigo);

        // Crear sección con márgenes
        $this->section = $this->phpWord->addSection([
            'marginLeft' => 1134,    // ~2cm
            'marginRight' => 1134,   // ~2cm
            'marginTop' => 1134,     // ~2cm
            'marginBottom' => 1134,  // ~2cm
        ]);
    }

    /**
     * Generar el documento completo
     */
    public function generate()
    {
        \Log::info('DEBUG Carta Data:', [
            'descripcion_servicios_type' => gettype($this->carta->descripcion_servicios),
            'descripcion_servicios_value' => $this->carta->descripcion_servicios,
            'productos_requeridos_type' => gettype($this->carta->productos_requeridos),
            'productos_requeridos_value' => $this->carta->productos_requeridos,
            'servicios_requeridos_type' => gettype($this->carta->servicios_requeridos),
            'servicios_requeridos_value' => $this->carta->servicios_requeridos,
        ]);

        $this->addHeader();
        $this->addIntroduction();
        $this->addBackground();
        $this->addRequiredServices();
        $this->addRequiredProductsTable();  // ← NUEVA TABLA
        $this->addSchedule();
        $this->addBudget();
        $this->addEvaluationCriteria();
        $this->addSubmissionInstructions();
        $this->addFooter();

        return $this;
    }

    /**
     * Agregar encabezado del documento
     */
    protected function addHeader()
    {
        // TÍTULO PRINCIPAL: "SOLICITUD DE PROPUESTAS"
        $this->section->addText(
            'SOLICITUD DE PROPUESTAS',
            [
                'bold' => true,
                'size' => 16,  // 16pt según plantilla
                'name' => 'Arial'
            ],
            [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 240,
                'spaceBefore' => 480  // Espacio antes
            ]
        );

        $this->section->addTextBreak(1);

        // SUBTÍTULO: "Para la prestación de los siguientes servicios:"
        $this->section->addText(
            'Para la prestación de los siguientes servicios:',
            [
                'bold' => true,
                'size' => 14,  // 14pt según plantilla
                'name' => 'Arial'
            ],
            [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 120
            ]
        );

        $this->section->addTextBreak(1);

        // DESCRIPCIÓN DE SERVICIOS (del formulario)
        $this->section->addText(
            $this->sanitizeText($this->carta->descripcion_servicios, '[Descripción de servicios no proporcionada]'),
            [
                'size' => 14,  // 14pt según plantilla
                'name' => 'Arial',
                'bold' => false
            ],
            [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 240
            ]
        );

        $this->section->addTextBreak(2);

        // FECHA DE PUBLICACIÓN
        $publishDate = Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY');

        $this->section->addText(
            "PUBLICADA EL: {$publishDate}",
            [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial'
            ],
            [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 120
            ]
        );

        $this->section->addTextBreak(1);

        // FECHA LÍMITE (en rojo y más grande)
        $deadline = Carbon::parse($this->carta->fecha_fin)->locale('es')->isoFormat('D [de] MMMM [de] YYYY');

        $this->section->addText(
            "FECHA LÍMITE: el {$deadline} hasta las 17:00 horas GMT - 4",
            [
                'bold' => true,
                'size' => 14,  // 14pt según plantilla (más grande)
                'name' => 'Arial',
                'color' => 'FF0000'  // Rojo
            ],
            [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 240
            ]
        );

        $this->section->addTextBreak(2);

        // INFORMACIÓN DEL PROYECTO
        $this->section->addText(
            "Proyecto: {$this->carta->nombre_proyecto}",
            [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial'
            ],
            [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 120
            ]
        );

        $this->section->addTextBreak(1);

        // OFICINA FAO
        $oficinaText = $this->carta->oficina_fao ?? 'Oficina FAO Bolivia';
        $this->section->addText(
            "Oficina de la FAO: {$oficinaText}",
            [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial'
            ],
            [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 240
            ]
        );

        $this->section->addTextBreak(3);
    }

    /**
     * Agregar introducción
     */
    protected function addIntroduction()
    {
        $textStyle = ['size' => 11, 'name' => 'Arial'];
        $paragraphStyle = ['alignment' => Jc::BOTH, 'spaceAfter' => 120, 'lineHeight' => 1.15];

        // Párrafo introductorio según plantilla FAO
        $intro = "La Organización de las Naciones Unidas para la Alimentación y la Agricultura " .
            "(en adelante, la 'FAO') es una organización intergubernamental que cuenta con más de 194 Estados miembros. " .
        "La FAO tiene como objetivo erradicar el hambre, la inseguridad alimentaria y la malnutrición; " .
        "eliminar la pobreza e impulsar el progreso económico y social para todos; y gestionar y utilizar " .
        "de manera sostenible los recursos naturales, incluidos la tierra, el agua, el aire, el clima y " .
        "los recursos genéticos, en beneficio de las generaciones presentes y futuras.";

        $this->section->addText($intro, $textStyle, $paragraphStyle);

        // Información sobre la FAO
        $this->section->addText(
            "Para obtener información detallada sobre la FAO, consulte el sitio de Internet http://www.fao.org.",
            ['size' => 11, 'name' => 'Arial'],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 120]
        );

        // Información sobre Cartas de Acuerdo
        $cdaInfo = "Esta invitación es para la prestación de servicios no comerciales bajo las normas de la FAO " .
            "que rigen las Cartas de Acuerdo (en adelante, 'CdA'). Bajo las normas de las CdA, " .
        "los Proveedores de Servicios se comprometen a prestar sus servicios a la FAO sin ánimo de lucro, " .
        "es decir, sin obtener beneficios, ganancias ni cualquier otra contraprestación económica, " .
        "y sin cobrar ninguna suma con respecto a gastos generales y/o administrativos como honorarios del personal. " .
        "Todos los costos relacionados con el suministro de servicios están totalmente justificados.";

        $this->section->addText($cdaInfo, $textStyle, $paragraphStyle);

        $this->section->addTextBreak(2);
    }

    /**
     * Agregar antecedentes
     */
    protected function addBackground()
    {
        // Título de la sección (BOLD, sin subrayado en plantilla)
        $this->section->addText(
            'I. Antecedentes:',
            [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial'
            ],
            [
                'alignment' => Jc::BOTH,
                'spaceAfter' => 120
            ]
        );

        // Contenido de antecedentes
        $antecedentes = $this->sanitizeText(
            $this->carta->antecedentes,
            '[Insertar descripción del proyecto y/o del objetivo que hay que lograr mediante los resultados/productos requeridos del Proveedor de Servicios]'
        );

        $this->section->addText(
            $antecedentes,
            ['size' => 11, 'name' => 'Arial'],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 240, 'lineHeight' => 1.15]
        );

        // Línea punteada separadora (según plantilla)
        $this->section->addText(
            str_repeat('.', 170),
            ['size' => 11, 'color' => '000000'],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 240]
        );

        $this->section->addTextBreak(1);
    }

    /**
     * Agregar servicios requeridos
     */
    protected function addRequiredServices()
    {
        // Título de la sección
        $this->section->addText(
            'II. Servicios requeridos:',
            [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial'
            ],
            [
                'alignment' => Jc::BOTH,
                'spaceAfter' => 120
            ]
        );

        // Servicios requeridos del formulario
        if ($this->carta->servicios_requeridos) {
            // Convertir a array si es string
            if (is_string($this->carta->servicios_requeridos)) {
                $serviciosArray = explode(', ', $this->carta->servicios_requeridos);
            } elseif (is_array($this->carta->servicios_requeridos)) {
                $serviciosArray = $this->carta->servicios_requeridos;
            } else {
                $serviciosArray = [];
            }

            foreach ($serviciosArray as $index => $service) {
                $this->section->addListItem(
                    trim($service),
                    0,
                    ['size' => 11, 'name' => 'Arial'],
                    ['alignment' => Jc::BOTH, 'spaceAfter' => 80, 'lineHeight' => 1.15]
                );
            }
        } else {
            $this->section->addText(
                '[Incluir una lista detallada de los servicios requeridos del Proveedor de Servicios]',
                ['size' => 11, 'name' => 'Arial', 'italic' => true],
                ['alignment' => Jc::BOTH, 'spaceAfter' => 120]
            );
        }

        // Línea punteada separadora
        $this->section->addText(
            str_repeat('.', 170),
            ['size' => 11, 'color' => '000000'],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 240]
        );

        $this->section->addTextBreak(1);
    }

    /**
     * Agregar tabla de productos requeridos (según plantilla FAO)
     */
    protected function addRequiredProductsTable()
    {
        $this->section->addText(
            'III. Productos requeridos:',
            [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial'
            ],
            [
                'alignment' => Jc::BOTH,
                'spaceAfter' => 120
            ]
        );

        $this->section->addTextBreak(1);

        // Crear tabla con 3 columnas según plantilla
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'width' => 100 * 50,
        ];

        $cellHdrStyle = [
            'bgColor' => 'DDDDDD',  // Gris claro para encabezados
            'valign' => 'center',
        ];

        $textHdrStyle = [
            'bold' => true,
            'size' => 10,
            'name' => 'Arial',
        ];

        $this->phpWord->addTableStyle('ProductsTable', $tableStyle);
        $table = $this->section->addTable('ProductsTable');

        // ENCABEZADOS DE LA TABLA
        $table->addRow(800);

        $cell1 = $table->addCell(3500, $cellHdrStyle);
        $cell1->addText('Productos requeridos', $textHdrStyle, ['alignment' => Jc::LEFT]);

        $cell2 = $table->addCell(3500, $cellHdrStyle);
        $cell2->addText('Indicadores clave de rendimiento por producto', $textHdrStyle, ['alignment' => Jc::LEFT]);

        $cell3 = $table->addCell(2500, $cellHdrStyle);
        $cell3->addText('Cronograma de entrega de los productos', $textHdrStyle, ['alignment' => Jc::LEFT]);

        // FILA DE CONTENIDO
        $table->addRow();

        // Columna 1: Productos (convertir array a string si es necesario)
        $productosText = $this->sanitizeText(
            $this->carta->productos_requeridos,
            '[Insertar lista numerada de resultados]'
        );

        $table->addCell(3500)->addText(
            $productosText,
            ['size' => 10, 'name' => 'Arial'],
            ['alignment' => Jc::LEFT]
        );

        // Columna 2: Indicadores (KPIs) (convertir array a string si es necesario)
        $kpisText = $this->sanitizeText(
            $this->carta->indicadores_kpi,
            '[Insertar indicadores clave de rendimiento]'
        );

        $table->addCell(3500)->addText(
            $kpisText,
            ['size' => 10, 'name' => 'Arial'],
            ['alignment' => Jc::LEFT]
        );

        // Columna 3: Cronograma
        $fechaInicio = Carbon::parse($this->carta->fecha_inicio)->locale('es')->isoFormat('D/MM/YYYY');
        $fechaFin = Carbon::parse($this->carta->fecha_fin)->locale('es')->isoFormat('D/MM/YYYY');
        $cronogramaText = "Inicio: {$fechaInicio}\nFin: {$fechaFin}";

        $table->addCell(2500)->addText(
            $cronogramaText,
            ['size' => 10, 'name' => 'Arial'],
            ['alignment' => Jc::LEFT]
        );

        $this->section->addTextBreak(2);

        // Línea punteada separadora
        $this->section->addText(
            str_repeat('.', 170),
            ['size' => 11, 'color' => '000000'],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 240]
        );
    }

    /**
     * Agregar productos requeridos
     */
    protected function addRequiredProducts()
    {
        // Título de la sección
        $this->section->addText(
            'III. Productos requeridos:',
            ['bold' => true, 'size' => 12, 'name' => 'Arial', 'underline' => 'single'],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 120]
        );

        // Crear tabla de productos
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => Jc::CENTER,
            'width' => 100 * 50,
        ];

        $cellHdrStyle = [
            'bgColor' => '4472C4',
            'valign' => 'center',
        ];

        $textHdrStyle = [
            'bold' => true,
            'size' => 10,
            'name' => 'Arial',
            'color' => 'FFFFFF',
        ];

        $cellStyle = ['size' => 10, 'name' => 'Arial'];

        $this->phpWord->addTableStyle('ProductTable', $tableStyle);
        $table = $this->section->addTable('ProductTable');

        // Encabezados
        $table->addRow(400);
        $cell1 = $table->addCell(3000, $cellHdrStyle);
        $cell1->addText('Productos requeridos', $textHdrStyle, ['alignment' => Jc::CENTER]);

        $cell2 = $table->addCell(3000, $cellHdrStyle);
        $cell2->addText('Indicadores clave de rendimiento', $textHdrStyle, ['alignment' => Jc::CENTER]);

        $cell3 = $table->addCell(3000, $cellHdrStyle);
        $cell3->addText('Cronograma de entrega', $textHdrStyle, ['alignment' => Jc::CENTER]);

        // Los productos pueden venir como array JSON o string
        $productosArray = [];

        if (is_array($this->carta->productos_requeridos)) {
            $productosArray = $this->carta->productos_requeridos;
        } elseif (is_string($this->carta->productos_requeridos)) {
            // Intentar decodificar como JSON
            $decoded = json_decode($this->carta->productos_requeridos, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $productosArray = $decoded;
            } else {
                // Si no es JSON, separar por comas
                $productosArray = explode(',', $this->carta->productos_requeridos);
            }
        }

        // Filas de productos
        if (count($productosArray) > 0 && !empty($productosArray[0])) {
            foreach ($productosArray as $product) {
                $table->addRow();
                $table->addCell(3000)->addText(trim($product), $cellStyle);
                $table->addCell(3000)->addText('Por definir', $cellStyle);
                $table->addCell(3000)->addText(
                    Carbon::parse($this->carta->fecha_fin)->format('d/m/Y'),
                    $cellStyle,
                    ['alignment' => Jc::CENTER]
                );
            }
        } else {
            $table->addRow();
            $table->addCell(9000, ['gridSpan' => 3])->addText(
                'No se especificaron productos requeridos.',
                ['size' => 10, 'name' => 'Arial', 'italic' => true],
                ['alignment' => Jc::CENTER]
            );
        }

        $this->section->addTextBreak(1);
    }

    /**
     * Agregar cronograma
     */
    protected function addSchedule()
    {
        $this->section->addText(
            'IV. Cronograma:',
            ['bold' => true, 'size' => 12, 'name' => 'Arial', 'underline' => 'single'],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 120]
        );

        $startDate = Carbon::parse($this->carta->fecha_inicio)->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
        $endDate = Carbon::parse($this->carta->fecha_fin)->locale('es')->isoFormat('D [de] MMMM [de] YYYY');

        $this->section->addText(
            "Fecha de inicio: {$startDate}",
            ['size' => 11, 'name' => 'Arial'],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 80]
        );

        $this->section->addText(
            "Fecha de finalización: {$endDate}",
            ['size' => 11, 'name' => 'Arial'],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 240]
        );

        $duracion = Carbon::parse($this->carta->fecha_inicio)->diffInDays(Carbon::parse($this->carta->fecha_fin));
        $this->section->addText(
            "Duración estimada: {$duracion} días",
            ['size' => 11, 'name' => 'Arial', 'italic' => true],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 240]
        );

        $this->section->addTextBreak(1);
    }

    /**
     * Agregar presupuesto
     */
    protected function addBudget()
    {
        $this->section->addText(
            'V. Presupuesto:',
            ['bold' => true, 'size' => 12, 'name' => 'Arial', 'underline' => 'single'],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 120]
        );

        $amount = $this->carta->monto_total
            ? number_format($this->carta->monto_total, 2, ',', '.')
            : 'Por definir';

        $currency = $this->carta->moneda ?? 'USD';

        $this->section->addText(
            "Monto total estimado: {$amount} {$currency}",
            ['size' => 11, 'name' => 'Arial', 'bold' => true],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 120]
        );

        $this->section->addText(
            "Nota: Este monto es estimado y está sujeto a la propuesta técnica y económica presentada.",
            ['size' => 10, 'name' => 'Arial', 'italic' => true],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 240]
        );

        $this->section->addTextBreak(1);
    }

    /**
     * Agregar criterios de evaluación
     */
    protected function addEvaluationCriteria()
    {
        $this->section->addText(
            'VI. Criterios de evaluación:',
            ['bold' => true, 'size' => 12, 'name' => 'Arial', 'underline' => 'single'],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 120]
        );

        $this->section->addText(
            'Las propuestas serán evaluadas de acuerdo a los siguientes criterios:',
            ['size' => 11, 'name' => 'Arial'],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 120]
        );

        $criteria = [
            'Experiencia técnica y capacidad del proveedor' => '30%',
            'Calidad de la propuesta técnica' => '30%',
            'Metodología propuesta' => '20%',
            'Propuesta económica' => '20%',
        ];

        // Crear tabla de criterios
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'width' => 100 * 50,
        ];

        $cellHdrStyle = [
            'bgColor' => '4472C4',
            'valign' => 'center',
        ];

        $textHdrStyle = [
            'bold' => true,
            'size' => 10,
            'name' => 'Arial',
            'color' => 'FFFFFF',
        ];

        $this->phpWord->addTableStyle('CriteriaTable', $tableStyle);
        $table = $this->section->addTable('CriteriaTable');

        // Encabezados
        $table->addRow(400);
        $cell1 = $table->addCell(6000, $cellHdrStyle);
        $cell1->addText('Criterios', $textHdrStyle, ['alignment' => Jc::CENTER]);

        $cell2 = $table->addCell(3000, $cellHdrStyle);
        $cell2->addText('Ponderación', $textHdrStyle, ['alignment' => Jc::CENTER]);

        // Filas
        foreach ($criteria as $criterion => $weight) {
            $table->addRow();
            $table->addCell(6000)->addText($criterion, ['size' => 10, 'name' => 'Arial']);
            $table->addCell(3000)->addText(
                $weight,
                ['size' => 10, 'name' => 'Arial', 'bold' => true],
                ['alignment' => Jc::CENTER]
            );
        }

        $this->section->addTextBreak(1);
    }

    /**
     * Agregar instrucciones de presentación
     */
    protected function addSubmissionInstructions()
    {
        $this->section->addText(
            'VII. Instrucciones para la presentación:',
            ['bold' => true, 'size' => 12, 'name' => 'Arial', 'underline' => 'single'],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 120]
        );

        $instructions = [
            'La propuesta debe incluir: propuesta técnica detallada, propuesta económica con desglose de costos, y documentación de respaldo que acredite la experiencia.',
            'Todas las propuestas deben ser presentadas antes de la fecha límite indicada en este documento.',
            'Las propuestas incompletas o presentadas fuera de plazo no serán consideradas para evaluación.',
            'Se realizará una evaluación técnica y económica de todas las propuestas recibidas conforme a los criterios establecidos.',
            'La FAO se reserva el derecho de solicitar aclaraciones o información adicional durante el proceso de evaluación.',
        ];

        foreach ($instructions as $instruction) {
            $this->section->addListItem(
                $instruction,
                0,
                ['size' => 11, 'name' => 'Arial'],
                ['alignment' => Jc::BOTH, 'spaceAfter' => 80, 'lineHeight' => 1.15]
            );
        }

        $this->section->addTextBreak(1);
    }

    /**
     * Agregar pie de página
     */
    protected function addFooter()
    {
        $this->section->addTextBreak(2);

        $this->section->addText(
            'Información de contacto:',
            ['bold' => true, 'size' => 11, 'name' => 'Arial'],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 120]
        );

        if ($this->carta->responsable_fao_nombre) {
            $this->section->addText(
                "Responsable: {$this->carta->responsable_fao_nombre}",
                ['size' => 10, 'name' => 'Arial'],
                ['alignment' => Jc::BOTH, 'spaceAfter' => 60]
            );
        }

        if ($this->carta->responsable_fao_email) {
            $this->section->addText(
                "Email: {$this->carta->responsable_fao_email}",
                ['size' => 10, 'name' => 'Arial'],
                ['alignment' => Jc::BOTH, 'spaceAfter' => 60]
            );
        }

        if ($this->carta->responsable_fao_telefono) {
            $this->section->addText(
                "Teléfono: {$this->carta->responsable_fao_telefono}",
                ['size' => 10, 'name' => 'Arial'],
                ['alignment' => Jc::BOTH, 'spaceAfter' => 120]
            );
        }

        $this->section->addTextBreak(1);

        $footer = "Para mayor información o consultas sobre esta invitación, " .
            "favor contactar mediante los canales oficiales proporcionados.";

        $this->section->addText(
            $footer,
            ['size' => 10, 'name' => 'Arial', 'italic' => true],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 120]
        );
    }

    /**
     * Guardar el documento
     */
    public function save($filename = null)
    {
        if (!$filename) {
            // Nombre del archivo con código de carta y timestamp
            $cleanCodigo = preg_replace('/[^A-Za-z0-9\-]/', '_', $this->carta->codigo);
            $filename = "invitation_{$cleanCodigo}_" . time() . '.docx';
        }

        $filepath = storage_path('app/public/invitations/' . $filename);

        // Crear directorio si no existe
        $directory = dirname($filepath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');
        $objWriter->save($filepath);

        return $filepath;
    }

    /**
     * Descargar el documento
     */
    public function download($filename = null)
    {
        if (!$filename) {
            $cleanCodigo = preg_replace('/[^A-Za-z0-9\-]/', '_', $this->carta->codigo);
            $filename = "invitation_{$cleanCodigo}.docx";
        }

        $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $objWriter->save('php://output');
        exit;
    }

    /**
     * Obtener el objeto PhpWord
     */
    public function getPhpWord()
    {
        return $this->phpWord;
    }

    protected function sanitizeText($value, $default = '')
    {
        if (is_array($value)) {
            return implode("\n", array_map(function($item, $index) {
                return ($index + 1) . '. ' . trim($item);
            }, $value, array_keys($value)));
        }

        if (is_null($value) || $value === '') {
            return $default;
        }

        return (string) $value;
    }
}
