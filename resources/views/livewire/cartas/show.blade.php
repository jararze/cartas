<?php

use App\Models\Carta;
use App\Models\Producto;
use App\Models\Actividad;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Carta $carta;
    public $showKPIModal = false;
    public $showCollaboratorsModal = false;
    public $showProductModal = false;
    public $showActivityModal = false;
    public $selectedProducto = null;

    // ✅ NUEVAS PROPIEDADES PARA BÚSQUEDA Y PAGINACIÓN
    public $searchProductos = '';
    public $searchActividades = '';
    public $selectedProductoIdForDetail = null;
    public $perPageProductos = 10;
    public $perPageActividades = 12;

    // Formulario nuevo producto
    public $producto_nombre = '';
    public $producto_descripcion = '';
    public $producto_presupuesto = '';
    public $producto_fecha_inicio = '';
    public $producto_fecha_fin = '';
    public $producto_kpis = [];

    // Formulario nueva actividad
    public $actividad_nombre = '';
    public $actividad_descripcion = '';
    public $actividad_presupuesto = '';
    public $actividad_fecha_inicio = '';
    public $actividad_fecha_fin = '';
    public $actividad_linea_presupuestaria = '';

    // Colaboradores
    public $colaborador_email = '';
    public $colaborador_telefono = '';
    public $colaborador_mensaje = '';

    public $showSeguimientoModal = false;
    public $selectedActividad = null;

// Formulario de seguimiento
    public $nuevo_progreso = '';
    public $nuevo_gasto = '';
    public $descripcion_avance = '';
    public $responsable_avance = '';

    public $logros = '';
    public $dificultades = '';
    public $proximos_pasos = '';
    public $proxima_revision = '';
    public $nivel_riesgo = 'bajo';
    public $riesgos_identificados = '';
    public $acciones_correctivas = '';
    public $etiquetas = [];
    public $observaciones = '';
    public $justificacion_sobregiro = '';
    public $archivos = [];
    public $imagenes = [];

    // ✅ AGREGAR ESTAS PROPIEDADES
    public $showFechasWarningModal = false;
    public $fechasWarningMessage = '';
    public $tipoCreacionPendiente = null; // 'producto' o 'actividad'

    // ✅ PROPIEDADES PARA VALIDACIONES MEJORADAS
    public $showAdvertenciasModal = false;
    public $advertenciasMontos = [];
    public $advertenciasFechas = [];
    public $tipoAdvertencia = null; // 'producto', 'actividad'

    public function mount(Carta $carta): void
    {
        $this->carta = $carta->load(['productos.actividades', 'colaboradores']);
    }

    public function with(): array
    {
        // Obtener productos con paginación y búsqueda
        $productosQuery = $this->carta->productos()
            ->with(['actividades']);

        // Filtro de búsqueda para productos
        if ($this->searchProductos) {
            $productosQuery->where(function($q) {
                $q->where('nombre', 'like', '%' . $this->searchProductos . '%')
                    ->orWhere('descripcion', 'like', '%' . $this->searchProductos . '%');
            });
        }

        $productos = $productosQuery->orderBy('orden')->paginate($this->perPageProductos, ['*'], 'productosPage');

        // Si hay un producto seleccionado, obtener sus actividades con paginación
        $actividadesProductoSeleccionado = null;
        if ($this->selectedProductoIdForDetail) {
            $actividadesQuery = Actividad::where('producto_id', $this->selectedProductoIdForDetail);

            // Filtro de búsqueda para actividades
            if ($this->searchActividades) {
                $actividadesQuery->where(function($q) {
                    $q->where('nombre', 'like', '%' . $this->searchActividades . '%')
                        ->orWhere('descripcion', 'like', '%' . $this->searchActividades . '%')
                        ->orWhere('linea_presupuestaria', 'like', '%' . $this->searchActividades . '%');
                });
            }

            $actividadesProductoSeleccionado = $actividadesQuery
                ->orderBy('fecha_inicio')
                ->paginate($this->perPageActividades, ['*'], 'actividadesPage');
        }

        // ✅ PRESUPUESTO REFERENCIAL CORREGIDO
        $presupuestoReferencial = $this->carta->monto_total ?? 0;

        // Calcular presupuesto total (suma de actividades)
        $totalPresupuesto = $this->carta->productos->sum(function ($producto) {
            return $producto->actividades->sum('monto');
        });

        $totalEjecutado = $this->carta->productos->sum(function ($producto) {
            return $producto->actividades->sum('gasto_acumulado');
        });

        $saldoDisponible = $totalPresupuesto - $totalEjecutado;
        $porcentajeEjecutado = $totalPresupuesto > 0 ? round(($totalEjecutado / $totalPresupuesto) * 100) : 0;

        // Calcular diferencia entre referencial y real
        $diferenciaPpto = $totalPresupuesto - $presupuestoReferencial;
        $excedePptoReferencial = $diferenciaPpto > 0;
        $porcentajeVariacion = $presupuestoReferencial > 0 ? round(($diferenciaPpto / $presupuestoReferencial) * 100, 1) : 0;

        $totalActividades = $this->carta->productos->sum(function ($producto) {
            return $producto->actividades->count();
        });
        $actividadesCompletadas = $this->carta->productos->sum(function ($producto) {
            return $producto->actividades->where('progreso', 100)->count();
        });
        $actividadesEnCurso = $this->carta->productos->sum(function ($producto) {
            return $producto->actividades->where('progreso', '>', 0)->where('progreso', '<', 100)->count();
        });
        $actividadesPendientes = $totalActividades - $actividadesCompletadas - $actividadesEnCurso;

        $progresoGeneral = $totalActividades > 0 ?
            round($this->carta->productos->sum(function ($producto) {
                    return $producto->actividades->avg('progreso') ?? 0;
                }) / $this->carta->productos->count()) : 0;

        return [
            'productos' => $productos,
            'actividadesProductoSeleccionado' => $actividadesProductoSeleccionado,
            'productoSeleccionadoObj' => $this->selectedProductoIdForDetail ? Producto::find($this->selectedProductoIdForDetail) : null,
            'presupuestoReferencial' => $presupuestoReferencial,
            'totalPresupuesto' => $totalPresupuesto,
            'totalEjecutado' => $totalEjecutado,
            'saldoDisponible' => $saldoDisponible,
            'porcentajeEjecutado' => $porcentajeEjecutado,
            'diferenciaPpto' => $diferenciaPpto,
            'excedePptoReferencial' => $excedePptoReferencial,
            'porcentajeVariacion' => $porcentajeVariacion,
            'totalActividades' => $totalActividades,
            'actividadesCompletadas' => $actividadesCompletadas,
            'actividadesEnCurso' => $actividadesEnCurso,
            'actividadesPendientes' => $actividadesPendientes,
            'progresoGeneral' => $progresoGeneral,
        ];
    }

    public function openSeguimientoModal($actividadId): void
    {
        $this->selectedActividad = Actividad::find($actividadId);

        // Limpiar TODOS los campos del formulario
        $this->reset([
            'nuevo_progreso',
            'nuevo_gasto',
            'descripcion_avance',
            'responsable_avance',
            'logros',
            'dificultades',
            'proximos_pasos',
            'proxima_revision',
            'nivel_riesgo',
            'riesgos_identificados',
            'acciones_correctivas',
            'etiquetas',
            'observaciones',
            'justificacion_sobregiro',
            'archivos',
            'imagenes'
        ]);

        // Establecer valores por defecto
        if ($this->selectedActividad) {
            $this->nuevo_progreso = $this->selectedActividad->progreso;
            $this->nivel_riesgo = 'bajo'; // Valor por defecto
        }

        $this->showSeguimientoModal = true;
    }

    public function registrarSeguimiento(): void
    {
        // Validación básica
        $this->validate([
            'nuevo_progreso' => 'required|numeric|min:0|max:100',
            'nuevo_gasto' => 'required|numeric|min:0',
            'descripcion_avance' => 'required|string|min:10',
            'responsable_avance' => 'required|string|min:3',
            'logros' => 'nullable|string',
            'dificultades' => 'nullable|string',
            'proximos_pasos' => 'nullable|string',
            'proxima_revision' => 'nullable|date',
            'nivel_riesgo' => 'required|in:bajo,medio,alto,critico',
            'riesgos_identificados' => 'nullable|string',
            'acciones_correctivas' => 'nullable|string',
            'etiquetas' => 'nullable|array',
            'observaciones' => 'nullable|string',
            'justificacion_sobregiro' => 'nullable|string',
        ], [
            'nuevo_progreso.required' => 'El progreso es obligatorio',
            'nuevo_progreso.min' => 'El progreso debe ser al menos 0',
            'nuevo_progreso.max' => 'El progreso no puede ser mayor a 100',
            'nuevo_gasto.required' => 'El monto gastado es obligatorio',
            'nuevo_gasto.min' => 'El monto gastado no puede ser negativo',
            'descripcion_avance.required' => 'La descripción del avance es obligatoria',
            'descripcion_avance.min' => 'La descripción debe tener al menos 10 caracteres',
            'responsable_avance.required' => 'El responsable es obligatorio',
            'responsable_avance.min' => 'El nombre del responsable debe tener al menos 3 caracteres',
            'nivel_riesgo.required' => 'Debe seleccionar un nivel de riesgo',
        ]);

        // Validaciones de lógica de negocio
        if ($this->nuevo_progreso < $this->selectedActividad->progreso) {
            session()->flash('error', '❌ El nuevo progreso no puede ser menor al actual');
            return;
        }

        $nuevoGastoAcumulado = $this->selectedActividad->gasto_acumulado + $this->nuevo_gasto;
        $saldoDisponible = $this->selectedActividad->monto - $nuevoGastoAcumulado;
        $excedePpto = $saldoDisponible < 0;

        // Si excede presupuesto, requiere justificación
        if ($excedePpto && empty($this->justificacion_sobregiro)) {
            session()->flash('error', '❌ Se requiere justificación para el sobrepresupuesto');
            return;
        }

        // Calcular métricas
        $progresoAnterior = $this->selectedActividad->progreso;
        $gastoAnterior = $this->selectedActividad->gasto_acumulado;
        $variacionPresupuesto = $this->nuevo_gasto - ($this->selectedActividad->monto - $gastoAnterior);
        $variacionPorcentaje = $this->selectedActividad->monto > 0
            ? (($nuevoGastoAcumulado / $this->selectedActividad->monto) * 100) - (($gastoAnterior / $this->selectedActividad->monto) * 100)
            : 0;

        // Calcular índice de eficiencia
        $indiceEficiencia = $nuevoGastoAcumulado > 0
            ? ($this->nuevo_progreso / (($nuevoGastoAcumulado / $this->selectedActividad->monto) * 100))
            : 0;

        // Determinar nuevo estado
        $nuevoEstado = match (true) {
            $this->nuevo_progreso == 100 => 'finalizado',
            $this->nuevo_progreso > 0 => 'en_curso',
            default => 'pendiente'
        };

        // Si está atrasado (fecha_fin pasada y progreso < 100)
        $estaAtrasado = now()->gt($this->selectedActividad->fecha_fin) && $this->nuevo_progreso < 100;
        if ($estaAtrasado) {
            $nuevoEstado = 'atrasado';
        }

        // Crear registro de seguimiento
        $seguimiento = $this->selectedActividad->seguimientos()->create([
            'progreso_anterior' => $progresoAnterior,
            'progreso_nuevo' => $this->nuevo_progreso,
            'monto_gastado' => $this->nuevo_gasto,
            'gasto_acumulado_anterior' => $gastoAnterior,
            'gasto_acumulado_nuevo' => $nuevoGastoAcumulado,
            'descripcion_avance' => $this->descripcion_avance,
            'logros' => $this->logros,
            'dificultades' => $this->dificultades,
            'proximos_pasos' => $this->proximos_pasos,
            'proxima_revision' => $this->proxima_revision,
            'responsable_nombre' => $this->responsable_avance,
            'observaciones' => $this->observaciones,
            'estado_anterior' => $this->selectedActividad->estado,
            'estado_nuevo' => $nuevoEstado,
            'excede_presupuesto' => $excedePpto,
            'esta_atrasado' => $estaAtrasado,
            'variacion_presupuesto' => $variacionPresupuesto,
            'variacion_presupuesto_porcentaje' => $variacionPorcentaje,
            'indice_eficiencia' => $indiceEficiencia,
            'nivel_riesgo' => $this->nivel_riesgo,
            'riesgos_identificados' => $this->riesgos_identificados,
            'acciones_correctivas' => $this->acciones_correctivas,
            'etiquetas' => $this->etiquetas,
            'registrado_por' => auth()->id(),
            'fecha_registro' => now(),
            'estado_revision' => $excedePpto ? 'requiere_cambios' : 'pendiente',
        ]);

        // Si hay justificación de sobregiro, guardarla en observaciones
        if ($excedePpto && $this->justificacion_sobregiro) {
            $seguimiento->update([
                'observaciones' => "JUSTIFICACIÓN SOBREPRESUPUESTO: {$this->justificacion_sobregiro}\n\n".($this->observaciones ?? '')
            ]);
        }

        // Actualizar la actividad
        $this->selectedActividad->update([
            'progreso' => $this->nuevo_progreso,
            'gasto_acumulado' => $nuevoGastoAcumulado,
            'estado' => $nuevoEstado,
        ]);

        // Manejo de archivos (implementar según tu sistema de storage)
        // $this->handleFileUploads($seguimiento);

        $this->showSeguimientoModal = false;
        $this->carta->refresh();

        $mensaje = $excedePpto
            ? '⚠️ Seguimiento registrado. ATENCIÓN: Se excedió el presupuesto en $'.number_format(abs($saldoDisponible),
                2)
            : '✅ Seguimiento registrado exitosamente';

        session()->flash('message', $mensaje);
    }

    public function openProductModal(): void
    {
        $this->reset([
            'producto_nombre', 'producto_descripcion', 'producto_presupuesto', 'producto_fecha_inicio',
            'producto_fecha_fin'
        ]);
        $this->showProductModal = true;
    }

    public function createProducto(): void
    {
        $this->validate([
            'producto_nombre' => 'required|min:3',
            'producto_descripcion' => 'required|min:10',
            'producto_presupuesto' => 'required|numeric|min:0',
            'producto_fecha_inicio' => 'required|date',
            'producto_fecha_fin' => 'required|date|after:producto_fecha_inicio',
        ]);

        // ✅ RESETEAR ADVERTENCIAS
        $this->advertenciasMontos = [];
        $this->advertenciasFechas = [];

        $hayAdvertencias = false;

        // ═══════════════════════════════════════════════════════════
        // 📅 VALIDACIONES DE FECHAS DEL PRODUCTO
        // ═══════════════════════════════════════════════════════════

        $cartaFechaInicio = $this->carta->fecha_inicio ? \Carbon\Carbon::parse($this->carta->fecha_inicio) : null;
        $cartaFechaFin = $this->carta->fecha_fin ? \Carbon\Carbon::parse($this->carta->fecha_fin) : null;
        $productoFechaInicio = \Carbon\Carbon::parse($this->producto_fecha_inicio);
        $productoFechaFin = \Carbon\Carbon::parse($this->producto_fecha_fin);

        $problemasCarta = [];

        if ($cartaFechaInicio && $productoFechaInicio->lt($cartaFechaInicio)) {
            $hayAdvertencias = true;
            $problemasCarta[] = "La fecha de inicio del producto (<strong>{$productoFechaInicio->format('d/m/Y')}</strong>) es <strong>anterior</strong> a la fecha de inicio de la carta (<strong>{$cartaFechaInicio->format('d/m/Y')}</strong>)";
        }

        if ($cartaFechaFin && $productoFechaFin->gt($cartaFechaFin)) {
            $hayAdvertencias = true;
            $problemasCarta[] = "La fecha fin del producto (<strong>{$productoFechaFin->format('d/m/Y')}</strong>) es <strong>posterior</strong> a la fecha fin de la carta (<strong>{$cartaFechaFin->format('d/m/Y')}</strong>)";
        }

        if (!empty($problemasCarta)) {
            $this->advertenciasFechas['carta'] = [
                'codigo' => $this->carta->codigo,
                'fecha_inicio' => $cartaFechaInicio?->format('d/m/Y') ?? 'No definida',
                'fecha_fin' => $cartaFechaFin?->format('d/m/Y') ?? 'No definida',
                'problemas' => $problemasCarta,
            ];
        }

        // Si hay advertencias, mostrar modal
        if ($hayAdvertencias) {
            $this->tipoAdvertencia = 'producto';
            $this->showAdvertenciasModal = true;
            return;
        }

        // Crear producto normalmente
        $this->ejecutarCreacionProducto();
    }

    public function ejecutarCreacionProducto(): void
    {
        $this->carta->productos()->create([
            'nombre' => $this->producto_nombre,
            'descripcion' => $this->producto_descripcion,
            'presupuesto' => $this->producto_presupuesto,
            'fecha_inicio' => $this->producto_fecha_inicio,
            'fecha_fin' => $this->producto_fecha_fin,
            'indicadores_kpi' => [],
            'orden' => $this->carta->productos->count() + 1,
        ]);

        $this->showProductModal = false;
        $this->showFechasWarningModal = false;
        $this->carta->refresh();

        if ($this->tipoCreacionPendiente === 'producto') {
            session()->flash('message', '⚠️ Producto creado con fechas fuera del rango de la carta');
        } else {
            session()->flash('message', '✅ Producto creado exitosamente');
        }

        $this->tipoCreacionPendiente = null;
    }

    public function openActivityModal($productoId): void
    {
        $this->selectedProducto = $productoId;
        $this->reset([
            'actividad_nombre', 'actividad_descripcion', 'actividad_presupuesto', 'actividad_fecha_inicio',
            'actividad_fecha_fin', 'actividad_linea_presupuestaria'
        ]);
        $this->showActivityModal = true;
    }

    public function createActividad(): void
    {
        $this->validate([
            'actividad_nombre' => 'required|min:3',
            'actividad_descripcion' => 'required|min:10',
            'actividad_presupuesto' => 'required|numeric|min:0',
            'actividad_fecha_inicio' => 'required|date',
            'actividad_fecha_fin' => 'required|date|after:actividad_fecha_inicio',
            'actividad_linea_presupuestaria' => 'required',
        ]);

        $producto = Producto::find($this->selectedProducto);

        // ✅ RESETEAR ADVERTENCIAS
        $this->advertenciasMontos = [];
        $this->advertenciasFechas = [];

        $hayAdvertencias = false;

        // ═══════════════════════════════════════════════════════════
        // 💰 VALIDACIONES DE MONTO
        // ═══════════════════════════════════════════════════════════

        $montoNuevaActividad = floatval($this->actividad_presupuesto);

        // 1️⃣ VALIDAR CONTRA PRESUPUESTO DEL PRODUCTO
        $presupuestoProducto = floatval($producto->presupuesto ?? 0);
        $sumaActividadesProducto = $producto->actividades->sum('monto');
        $nuevoTotalProducto = $sumaActividadesProducto + $montoNuevaActividad;
        $diferenciaProducto = $nuevoTotalProducto - $presupuestoProducto;

        if ($diferenciaProducto > 0 && $presupuestoProducto > 0) {
            $hayAdvertencias = true;
            $porcentajeExcesoProducto = round(($diferenciaProducto / $presupuestoProducto) * 100, 1);

            $this->advertenciasMontos['producto'] = [
                'nombre' => $producto->nombre,
                'presupuesto_asignado' => $presupuestoProducto,
                'suma_actual' => $sumaActividadesProducto,
                'monto_nueva' => $montoNuevaActividad,
                'nuevo_total' => $nuevoTotalProducto,
                'diferencia' => $diferenciaProducto,
                'porcentaje_exceso' => $porcentajeExcesoProducto,
            ];
        }

        // 2️⃣ VALIDAR CONTRA PRESUPUESTO REFERENCIAL DE LA CARTA
        $presupuestoReferencialCarta = floatval($this->carta->monto_total ?? 0);
        $sumaTotalActividadesCarta = $this->carta->productos->sum(function ($p) {
            return $p->actividades->sum('monto');
        });
        $nuevoTotalCarta = $sumaTotalActividadesCarta + $montoNuevaActividad;
        $diferenciaCarta = $nuevoTotalCarta - $presupuestoReferencialCarta;

        if ($diferenciaCarta > 0 && $presupuestoReferencialCarta > 0) {
            $hayAdvertencias = true;
            $porcentajeExcesoCarta = round(($diferenciaCarta / $presupuestoReferencialCarta) * 100, 1);

            $this->advertenciasMontos['carta'] = [
                'presupuesto_referencial' => $presupuestoReferencialCarta,
                'suma_actual' => $sumaTotalActividadesCarta,
                'monto_nueva' => $montoNuevaActividad,
                'nuevo_total' => $nuevoTotalCarta,
                'diferencia' => $diferenciaCarta,
                'porcentaje_exceso' => $porcentajeExcesoCarta,
            ];
        }

        // ═══════════════════════════════════════════════════════════
        // 📅 VALIDACIONES DE FECHAS
        // ═══════════════════════════════════════════════════════════

        $actividadFechaInicio = \Carbon\Carbon::parse($this->actividad_fecha_inicio);
        $actividadFechaFin = \Carbon\Carbon::parse($this->actividad_fecha_fin);

        // 1️⃣ VALIDAR CONTRA FECHAS DEL PRODUCTO
        $productoFechaInicio = $producto->fecha_inicio ? \Carbon\Carbon::parse($producto->fecha_inicio) : null;
        $productoFechaFin = $producto->fecha_fin ? \Carbon\Carbon::parse($producto->fecha_fin) : null;

        $problemasProducto = [];

        if ($productoFechaInicio && $actividadFechaInicio->lt($productoFechaInicio)) {
            $hayAdvertencias = true;
            $problemasProducto[] = "La fecha de inicio de la actividad (<strong>{$actividadFechaInicio->format('d/m/Y')}</strong>) es <strong>anterior</strong> a la fecha de inicio del producto (<strong>{$productoFechaInicio->format('d/m/Y')}</strong>)";
        }

        if ($productoFechaFin && $actividadFechaFin->gt($productoFechaFin)) {
            $hayAdvertencias = true;
            $problemasProducto[] = "La fecha fin de la actividad (<strong>{$actividadFechaFin->format('d/m/Y')}</strong>) es <strong>posterior</strong> a la fecha fin del producto (<strong>{$productoFechaFin->format('d/m/Y')}</strong>)";
        }

        if (!empty($problemasProducto)) {
            $this->advertenciasFechas['producto'] = [
                'nombre' => $producto->nombre,
                'fecha_inicio' => $productoFechaInicio?->format('d/m/Y') ?? 'No definida',
                'fecha_fin' => $productoFechaFin?->format('d/m/Y') ?? 'No definida',
                'problemas' => $problemasProducto,
            ];
        }

        // 2️⃣ VALIDAR CONTRA FECHAS DE LA CARTA
        $cartaFechaInicio = $this->carta->fecha_inicio ? \Carbon\Carbon::parse($this->carta->fecha_inicio) : null;
        $cartaFechaFin = $this->carta->fecha_fin ? \Carbon\Carbon::parse($this->carta->fecha_fin) : null;

        $problemasCarta = [];

        if ($cartaFechaInicio && $actividadFechaInicio->lt($cartaFechaInicio)) {
            $hayAdvertencias = true;
            $problemasCarta[] = "La fecha de inicio de la actividad (<strong>{$actividadFechaInicio->format('d/m/Y')}</strong>) es <strong>anterior</strong> a la fecha de inicio de la carta (<strong>{$cartaFechaInicio->format('d/m/Y')}</strong>)";
        }

        if ($cartaFechaFin && $actividadFechaFin->gt($cartaFechaFin)) {
            $hayAdvertencias = true;
            $problemasCarta[] = "La fecha fin de la actividad (<strong>{$actividadFechaFin->format('d/m/Y')}</strong>) es <strong>posterior</strong> a la fecha fin de la carta (<strong>{$cartaFechaFin->format('d/m/Y')}</strong>)";
        }

        if (!empty($problemasCarta)) {
            $this->advertenciasFechas['carta'] = [
                'codigo' => $this->carta->codigo,
                'fecha_inicio' => $cartaFechaInicio?->format('d/m/Y') ?? 'No definida',
                'fecha_fin' => $cartaFechaFin?->format('d/m/Y') ?? 'No definida',
                'problemas' => $problemasCarta,
            ];
        }

        // ═══════════════════════════════════════════════════════════
        // ✅ DECISIÓN FINAL
        // ═══════════════════════════════════════════════════════════

        if ($hayAdvertencias) {
            $this->tipoAdvertencia = 'actividad';
            $this->showAdvertenciasModal = true;
            return;
        }

        // Si no hay advertencias, crear actividad normalmente
        $this->ejecutarCreacionActividad();
    }

    public function ejecutarCreacionActividad(): void
    {
        $producto = Producto::find($this->selectedProducto);

        $producto->actividades()->create([
            'nombre' => $this->actividad_nombre,
            'descripcion' => $this->actividad_descripcion,
            'monto' => $this->actividad_presupuesto,
            'fecha_inicio' => $this->actividad_fecha_inicio,
            'fecha_fin' => $this->actividad_fecha_fin,
            'linea_presupuestaria' => $this->actividad_linea_presupuestaria,
            'estado' => 'pendiente',
            'progreso' => 0,
            'gasto_acumulado' => 0,
        ]);

        $this->showActivityModal = false;
        $this->showAdvertenciasModal = false;
        $this->carta->refresh();

        // Mensaje según si hubo advertencias
        if ($this->tipoAdvertencia === 'actividad') {
            session()->flash('message', '⚠️ Actividad creada con advertencias (revise montos y/o fechas)');
        } else {
            session()->flash('message', '✅ Actividad creada exitosamente');
        }

        // Limpiar advertencias
        $this->tipoAdvertencia = null;
        $this->advertenciasMontos = [];
        $this->advertenciasFechas = [];
    }

    public function confirmarCreacionConFechasFuera(): void
    {
        if ($this->tipoCreacionPendiente === 'producto') {
            $this->ejecutarCreacionProducto();
        } elseif ($this->tipoCreacionPendiente === 'actividad') {
            $this->ejecutarCreacionActividad();
        }
    }

    public function cancelarCreacionConFechasFuera(): void
    {
        $this->showFechasWarningModal = false;
        $this->tipoCreacionPendiente = null;
        $this->fechasWarningMessage = '';
    }

    public function crearActividadForzado(): void
    {
        $producto = Producto::find($this->selectedProducto);

        $producto->actividades()->create([
            'nombre' => $this->actividad_nombre,
            'descripcion' => $this->actividad_descripcion,
            'monto' => $this->actividad_presupuesto,
            'fecha_inicio' => $this->actividad_fecha_inicio,
            'fecha_fin' => $this->actividad_fecha_fin,
            'linea_presupuestaria' => $this->actividad_linea_presupuestaria,
            'estado' => 'pendiente',
            'progreso' => 0,
            'gasto_acumulado' => 0,
        ]);

        $this->showActivityModal = false;
        $this->carta->refresh();

        session()->flash('message', '⚠️ Actividad creada con fechas fuera del rango');
    }

    public function openCollaboratorsModal(): void
    {
        $this->reset(['colaborador_email', 'colaborador_telefono', 'colaborador_mensaje']);
        $this->showCollaboratorsModal = true;
    }

    public function inviteCollaborator(): void
    {
        $this->validate([
            'colaborador_email' => 'required|email',
            'colaborador_mensaje' => 'nullable|string|max:500',
        ]);

        // Aquí implementarías la lógica de invitación
        // Por ejemplo, crear un registro en la tabla colaboradores
        // y enviar email/WhatsApp

        session()->flash('message', '✅ Invitación enviada exitosamente');
        $this->showCollaboratorsModal = false;
    }

    public function getEstadoClass($estado): string
    {
        return match ($estado) {
            'finalizado' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
            'en_progreso' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
            'pendiente' => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
            default => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
        };
    }

    public function getProgressColor($progreso): string
    {
        if ($progreso >= 100) return 'bg-purple-500 dark:bg-purple-600';
        if ($progreso >= 70) return 'bg-green-500 dark:bg-green-600';
        if ($progreso >= 40) return 'bg-blue-500 dark:bg-blue-600';
        if ($progreso >= 20) return 'bg-yellow-500 dark:bg-yellow-600';
        return 'bg-gray-300 dark:bg-gray-600';
    }

    /**
     * Calcular el nuevo saldo después de registrar el gasto
     */
    public function getNuevoSaldoCalculado()
    {
        if (!$this->selectedActividad) {
            return 0;
        }

        $nuevoGastoNumerico = floatval($this->nuevo_gasto ?? 0);
        $montoActividad = floatval($this->selectedActividad->monto);
        $gastoAcumulado = floatval($this->selectedActividad->gasto_acumulado);

        return $montoActividad - ($gastoAcumulado + $nuevoGastoNumerico);
    }

    /**
     * Verificar si excede el presupuesto
     */
    public function getExcedePresupuestoCalculado()
    {
        return $this->getNuevoSaldoCalculado() < 0;
    }

    /**
     * Calcular índice de eficiencia
     */
    public function getIndiceEficienciaCalculado()
    {
        if (!$this->selectedActividad) {
            return 0;
        }

        $nuevoProgresoNumerico = floatval($this->nuevo_progreso ?? 0);
        $nuevoGastoNumerico = floatval($this->nuevo_gasto ?? 0);
        $gastoAcumulado = floatval($this->selectedActividad->gasto_acumulado);
        $monto = floatval($this->selectedActividad->monto);

        if ($nuevoProgresoNumerico <= 0 || $nuevoGastoNumerico <= 0) {
            return 0;
        }

        $gastoAcumuladoNuevo = $gastoAcumulado + $nuevoGastoNumerico;

        if ($gastoAcumuladoNuevo <= 0 || $monto <= 0) {
            return 0;
        }

        return $nuevoProgresoNumerico / (($gastoAcumuladoNuevo / $monto) * 100);
    }

    public function updatingSearchProductos()
    {
        $this->resetPage('productosPage');
    }

    public function updatingSearchActividades()
    {
        $this->resetPage('actividadesPage');
    }

    public function crearProductoForzado(): void
    {
        // Crear producto sin validación de fechas
        $this->carta->productos()->create([
            'nombre' => $this->producto_nombre,
            'descripcion' => $this->producto_descripcion,
            'presupuesto' => $this->producto_presupuesto,
            'fecha_inicio' => $this->producto_fecha_inicio,
            'fecha_fin' => $this->producto_fecha_fin,
            'indicadores_kpi' => [],
            'orden' => $this->carta->productos->count() + 1,
        ]);

        $this->showProductModal = false;
        $this->carta->refresh();

        session()->flash('message', '⚠️ Producto creado con fechas fuera del rango de la carta');
    }

    public function confirmarCreacionConAdvertencias(): void
    {
        if ($this->tipoAdvertencia === 'actividad') {
            $this->ejecutarCreacionActividad();
        } elseif ($this->tipoAdvertencia === 'producto') {
            $this->ejecutarCreacionProducto();
        }
    }

    public function cancelarCreacionConAdvertencias(): void
    {
        $this->showAdvertenciasModal = false;
        $this->tipoAdvertencia = null;
        $this->advertenciasMontos = [];
        $this->advertenciasFechas = [];
    }
}; ?>

<div class="min-h-screen">
    <!-- Header -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('cartas.index') }}"
                   class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                   wire:navigate>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $carta->codigo }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ $carta->nombre_proyecto }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto lg:px-8 py-8">

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div
                class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-700 dark:text-green-400">{{ session('message') }}</p>
            </div>
        @endif

        <!-- Resumen Ejecutivo -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">📊 Resumen Ejecutivo</h2>
                <div class="flex gap-3">
                    <a href="{{ route('cartas.kpis', $carta->id) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        Ver KPIs
                    </a>
                    <button wire:click="openCollaboratorsModal"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Colaboradores
                    </button>
                </div>
            </div>

            <!-- SECCIÓN 1: PRESUPUESTOS (3 NIVELES) -->
            <div class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 border-slate-200 dark:border-slate-700 p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    💰 Estructura Presupuestaria
                </h3>

                @php
                    $presupuestoProductos = $carta->productos->sum('presupuesto');
                    $diferenciaCartaProductos = $presupuestoProductos - $presupuestoReferencial;
                    $excedeCartaProductos = $diferenciaCartaProductos > 0;
                    $diferenciaTotalReal = $totalPresupuesto - $presupuestoReferencial;
                    $excedeTotalReal = $diferenciaTotalReal > 0;
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <!-- Nivel 1: Presupuesto Carta (Referencial Original) -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-2 border-indigo-200 dark:border-indigo-800">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Nivel 1: Carta</p>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400">Presupuesto Original</p>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                            ${{ number_format($presupuestoReferencial, 0) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Monto carta documento</p>
                    </div>

                    <!-- Nivel 2: Presupuesto Productos (Referencial Asignado) -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-2 border-blue-200 dark:border-blue-800">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Nivel 2: Productos</p>
                                <p class="text-xs text-blue-600 dark:text-blue-400">Presupuesto Asignado</p>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            ${{ number_format($presupuestoProductos, 0) }}
                        </p>
                        @if($excedeCartaProductos)
                            <p class="text-xs text-red-600 dark:text-red-400 font-semibold mt-1">
                                ⚠️ +${{ number_format($diferenciaCartaProductos, 0) }}
                            </p>
                        @elseif($diferenciaCartaProductos < 0)
                            <p class="text-xs text-green-600 dark:text-green-400 font-semibold mt-1">
                                ✓ -${{ number_format(abs($diferenciaCartaProductos), 0) }}
                            </p>
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $carta->productos->count() }} productos</p>
                        @endif
                    </div>

                    <!-- Nivel 3: Presupuesto Real (Suma Actividades) -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-2 {{ $excedeTotalReal ? 'border-red-200 dark:border-red-800' : 'border-green-200 dark:border-green-800' }}">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 {{ $excedeTotalReal ? 'bg-red-100 dark:bg-red-900/30' : 'bg-green-100 dark:bg-green-900/30' }} rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 {{ $excedeTotalReal ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Nivel 3: Actividades</p>
                                <p class="text-xs {{ $excedeTotalReal ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">Presupuesto Real</p>
                            </div>
                        </div>
                        <p class="text-2xl font-bold {{ $excedeTotalReal ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            ${{ number_format($totalPresupuesto, 0) }}
                        </p>
                        @if($excedeTotalReal)
                            <p class="text-xs text-red-600 dark:text-red-400 font-semibold mt-1">
                                ⚠️ +${{ number_format($diferenciaTotalReal, 0) }} vs Carta
                            </p>
                        @elseif($diferenciaTotalReal < 0)
                            <p class="text-xs text-green-600 dark:text-green-400 font-semibold mt-1">
                                ✓ -${{ number_format(abs($diferenciaTotalReal), 0) }} vs Carta
                            </p>
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Suma de actividades</p>
                        @endif
                    </div>
                </div>

                <!-- Alertas de Presupuesto -->
                @if($excedeCartaProductos || $excedeTotalReal)
                    <div class="space-y-2">
                        @if($excedeCartaProductos)
                            <div class="flex items-center gap-2 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg px-3 py-2">
                                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-xs font-semibold text-orange-800 dark:text-orange-200">
                                    Los productos asignados exceden el presupuesto de la carta por <strong>${{ number_format($diferenciaCartaProductos, 2) }}</strong>
                                </p>
                            </div>
                        @endif

                        @if($excedeTotalReal)
                            <div class="flex items-center gap-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg px-3 py-2">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-xs font-semibold text-red-800 dark:text-red-200">
                                    Las actividades exceden el presupuesto original de la carta por <strong>${{ number_format($diferenciaTotalReal, 2) }}</strong>
                                </p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- SECCIÓN 2: EJECUCIÓN Y PROGRESO -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Ejecutado -->
                <div class="text-center bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg mb-2">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Ejecutado</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                        ${{ number_format($totalEjecutado, 0) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $porcentajeEjecutado }}% del presupuesto real
                    </p>
                </div>

                <!-- Saldo Disponible -->
                <div class="text-center {{ $saldoDisponible < 0 ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' : 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800' }} rounded-lg p-4 border">
                    <div class="inline-flex items-center justify-center w-12 h-12 {{ $saldoDisponible < 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-blue-100 dark:bg-blue-900/30' }} rounded-lg mb-2">
                        <svg class="w-6 h-6 {{ $saldoDisponible < 0 ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Saldo Disponible</p>
                    <p class="text-2xl font-bold {{ $saldoDisponible < 0 ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400' }}">
                        ${{ number_format(abs($saldoDisponible), 0) }}
                    </p>
                    @if($saldoDisponible < 0)
                        <p class="text-xs text-red-600 dark:text-red-400 font-semibold mt-1">⚠️ Sobregiro</p>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ 100 - $porcentajeEjecutado }}% restante</p>
                    @endif
                </div>

                <!-- Progreso General -->
                <div class="text-center bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg mb-2">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Progreso General</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $progresoGeneral }}%</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $actividadesCompletadas }}/{{ $totalActividades }} actividades
                    </p>
                </div>
            </div>

            <!-- Barra de Ejecución -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Ejecución Presupuestaria</span>
                    <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $porcentajeEjecutado }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="bg-blue-600 dark:bg-blue-500 h-3 rounded-full transition-all"
                         style="width: {{ $porcentajeEjecutado }}%"></div>
                </div>
            </div>

            <!-- SECCIÓN 3: INFORMACIÓN DE FECHAS -->
            @if($carta->fecha_inicio && $carta->fecha_fin)
                @php
                    $fechaInicioCarta = \Carbon\Carbon::parse($carta->fecha_inicio);
                    $fechaFinCarta = \Carbon\Carbon::parse($carta->fecha_fin);
                    $hoy = \Carbon\Carbon::now();
                    $duracionTotal = $fechaInicioCarta->diffInDays($fechaFinCarta);
                    $diasTranscurridos = $fechaInicioCarta->lte($hoy) ? $fechaInicioCarta->diffInDays(min($hoy, $fechaFinCarta)) : 0;
                    $porcentajeTiempo = $duracionTotal > 0 ? round(($diasTranscurridos / $duracionTotal) * 100) : 0;
                    $estaAtrasado = $porcentajeTiempo > $progresoGeneral && $porcentajeTiempo > 10;
                    $estaEnPlazo = !$estaAtrasado;
                @endphp

                <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border-2 border-purple-200 dark:border-purple-800 p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        📅 Timeline del Proyecto
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div class="text-center bg-white dark:bg-gray-800 rounded-lg p-3">
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Fecha Inicio</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $fechaInicioCarta->format('d/m/Y') }}
                            </p>
                        </div>

                        <div class="text-center bg-white dark:bg-gray-800 rounded-lg p-3">
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Fecha Fin</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $fechaFinCarta->format('d/m/Y') }}
                            </p>
                        </div>

                        <div class="text-center bg-white dark:bg-gray-800 rounded-lg p-3">
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Duración Total</p>
                            <p class="text-sm font-bold text-purple-600 dark:text-purple-400">
                                {{ $duracionTotal }} días
                            </p>
                        </div>

                        <div class="text-center bg-white dark:bg-gray-800 rounded-lg p-3">
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Tiempo Transcurrido</p>
                            <p class="text-sm font-bold text-purple-600 dark:text-purple-400">
                                {{ $porcentajeTiempo }}%
                            </p>
                        </div>
                    </div>

                    <!-- Comparación Tiempo vs Progreso -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progreso vs Tiempo</span>
                            @if($estaAtrasado)
                                <span class="text-xs font-semibold text-red-600 dark:text-red-400">⚠️ Atrasado</span>
                            @else
                                <span class="text-xs font-semibold text-green-600 dark:text-green-400">✓ En plazo</span>
                            @endif
                        </div>

                        <!-- Barra de Tiempo -->
                        <div class="mb-2">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-gray-600 dark:text-gray-400">Tiempo</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ $porcentajeTiempo }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-gray-600 dark:bg-gray-500 h-2 rounded-full" style="width: {{ $porcentajeTiempo }}%"></div>
                            </div>
                        </div>

                        <!-- Barra de Progreso -->
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-gray-600 dark:text-gray-400">Progreso</span>
                                <span class="font-bold {{ $estaAtrasado ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $progresoGeneral }}%
                        </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="{{ $estaAtrasado ? 'bg-red-600 dark:bg-red-500' : 'bg-green-600 dark:bg-green-500' }} h-2 rounded-full" style="width: {{ $progresoGeneral }}%"></div>
                            </div>
                        </div>

                        @if($estaAtrasado)
                            <div class="mt-3 flex items-center gap-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded px-3 py-2">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-xs font-semibold text-red-800 dark:text-red-200">
                                    El proyecto está {{ $porcentajeTiempo - $progresoGeneral }}% atrasado respecto al tiempo transcurrido
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Stats Summary -->
            <div class="grid grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg mx-auto mb-2">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $actividadesCompletadas }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Completadas</p>
                </div>

                <div class="text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg mx-auto mb-2">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $actividadesEnCurso }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">En Curso</p>
                </div>

                <div class="text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg mx-auto mb-2">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $actividadesPendientes }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Pendientes</p>
                </div>
            </div>
        </div>

        <!-- Información del Proyecto y Colaboradores -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Información del Proyecto -->
            <div
                class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información del Proyecto</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Responsable FAO</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $carta->creador->name ?? 'No asignado' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Estado</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($carta->estado === 'aceptada') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                            @elseif($carta->estado === 'en_ejecucion') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                            @elseif($carta->estado === 'enviada') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                            {{ ucfirst(str_replace('_', ' ', $carta->estado)) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Fecha Inicio</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $carta->fecha_inicio ? \Carbon\Carbon::parse($carta->fecha_inicio)->format('d/m/Y') : 'No definida' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Fecha Fin</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $carta->fecha_fin ? \Carbon\Carbon::parse($carta->fecha_fin)->format('d/m/Y') : 'No definida' }}
                        </p>
                    </div>
                </div>

                @if($carta->descripcion_servicios)
                    <div class="mt-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Descripción</p>
                        <p class="text-gray-900 dark:text-white text-sm leading-relaxed">{{ $carta->descripcion_servicios }}</p>
                    </div>
                @endif
            </div>

            <!-- Colaboradores -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Colaboradores</h3>
                    <button wire:click="openCollaboratorsModal"
                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Invitar
                    </button>
                </div>

                <div class="space-y-3">
                    <!-- Creador del proyecto -->
                    <div class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div
                            class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-medium text-sm">
                            {{ substr($carta->creador->name ?? 'U', 0, 2) }}
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $carta->creador->name ?? 'Usuario' }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Responsable FAO</p>
                        </div>
                        <span class="text-xs text-blue-600 dark:text-blue-400 font-medium">Admin</span>
                    </div>

                    <!-- Proveedor -->
                    @if($carta->proveedor)
                        <div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div
                                class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-medium text-sm">
                                {{ substr($carta->proveedor->nombre ?? 'P', 0, 2) }}
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $carta->proveedor->nombre }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Proveedor</p>
                            </div>
                            <span class="text-xs text-green-600 dark:text-green-400 font-medium">Externo</span>
                        </div>
                    @endif

                    <!-- Colaboradores adicionales -->
                    @forelse($carta->colaboradores as $colaborador)
                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div
                                class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-white font-medium text-sm">
                                {{ substr($colaborador->name ?? 'C', 0, 2) }}
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $colaborador->name }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $colaborador->email }}</p>
                            </div>
                            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">Colaborador</span>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">No hay colaboradores adicionales</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Plan de Trabajo: Productos y Actividades (Master-Detail Pattern) -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md">

            <!-- Header con Buscador -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">📦 Plan de Trabajo</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ $carta->productos->count() }} producto(s) ·
                            {{ $carta->productos->sum(fn($p) => $p->actividades->count()) }} actividad(es) total
                        </p>
                    </div>
                    <button wire:click="openProductModal"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nuevo Producto
                    </button>
                </div>

                <!-- Buscador de Productos -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="searchProductos"
                        placeholder="Buscar productos por nombre o descripción..."
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
                    @if($searchProductos)
                        <button
                            wire:click="$set('searchProductos', '')"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            @if($productos->isEmpty())
                <!-- Estado vacío -->
                <div class="p-12 text-center">
                    @if($searchProductos)
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">No se encontraron productos</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">No hay productos que coincidan con "{{ $searchProductos }}"</p>
                        <button wire:click="$set('searchProductos', '')"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                            Limpiar búsqueda
                        </button>
                    @else
                        <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">No hay productos registrados</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">Comienza creando el primer producto del proyecto</p>
                        <button wire:click="openProductModal"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Crear Primer Producto
                        </button>
                    @endif
                </div>
            @else
                <!-- MASTER: Tabla de Productos -->
                <div class="overflow-x-auto border-b border-gray-200 dark:border-gray-700">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                Producto
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                Presupuesto
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                Ejecutado
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                Actividades
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                Progreso
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($productos as $producto)
                            @php
                                $presupuestoProducto = $producto->actividades->sum('monto');
                                $gastoProducto = $producto->actividades->sum('gasto_acumulado');
                                $saldoProducto = $presupuestoProducto - $gastoProducto;
                                $progresoProducto = $producto->actividades->avg('progreso') ?? 0;
                                $excedePresupuesto = $saldoProducto < 0;
                            @endphp

                            <tr
                                wire:click="$set('selectedProductoIdForDetail', {{ $producto->id }})"
                                class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ $selectedProductoIdForDetail == $producto->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">

                                <!-- Producto -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="w-1 h-12 rounded-full transition-colors {{ $selectedProductoIdForDetail == $producto->id ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                            </div>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h4 class="font-semibold text-gray-900 dark:text-white">
                                                    {{ $producto->nombre }}
                                                </h4>
                                                @if($excedePresupuesto)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                    ⚠️ Excedido
                                                </span>
                                                @endif
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-1">
                                                {{ $producto->descripcion }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Presupuesto -->
                                <td class="px-6 py-4 text-right">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        ${{ number_format($presupuestoProducto, 2) }}
                                    </div>
                                </td>

                                <!-- Ejecutado -->
                                <td class="px-6 py-4 text-right">
                                    <div class="text-sm font-semibold text-green-600 dark:text-green-400">
                                        ${{ number_format($gastoProducto, 2) }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $presupuestoProducto > 0 ? round(($gastoProducto / $presupuestoProducto) * 100) : 0 }}%
                                    </div>
                                </td>

                                <!-- Actividades -->
                                <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $producto->actividades->count() }}
                                </span>
                                </td>

                                <!-- Progreso -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="flex-1 max-w-20 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-purple-600 h-2 rounded-full" style="width: {{ round($progresoProducto) }}%"></div>
                                        </div>
                                        <span class="text-sm font-bold text-purple-600 dark:text-purple-400 min-w-[2.5rem]">
                                        {{ round($progresoProducto) }}%
                                    </span>
                                    </div>
                                </td>

                                <!-- Acciones -->
                                <td class="px-6 py-4" wire:click.stop>
                                    <div class="flex items-center justify-center gap-2">
                                        @can('update', $producto)
                                            <a href="{{ route('productos.edit', $producto) }}"
                                               wire:navigate
                                               class="p-2 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                               title="Editar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                        @endcan
                                        <button wire:click.stop="openActivityModal({{ $producto->id }})"
                                                class="p-2 text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                                                title="Nueva actividad">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación de Productos -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    {{ $productos->links() }}
                </div>

                <!-- DETAIL: Panel de Actividades del Producto Seleccionado -->
                @if($selectedProductoIdForDetail && $productoSeleccionadoObj)
                    <div id="detail-panel"
                         wire:key="detail-{{ $selectedProductoIdForDetail }}"
                         class="border-t-4 border-blue-600 dark:border-blue-500 bg-gradient-to-b from-blue-50/50 to-white dark:from-blue-900/10 dark:to-gray-800">

                        <div class="p-6">
                            <!-- Header del producto (nombre, botón cerrar y nueva actividad) -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                                            {{ $productoSeleccionadoObj->nombre }}
                                        </h3>
                                        <button wire:click="$set('selectedProductoIdForDetail', null)"
                                                class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $productoSeleccionadoObj->descripcion }}</p>
                                </div>

                                <button wire:click="openActivityModal({{ $productoSeleccionadoObj->id }})"
                                        class="ml-4 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Nueva Actividad
                                </button>
                            </div>

                            <!-- Panel de Métricas (FUERA DEL FLEX) -->
                            <div class="mb-6">
                                @php
                                    $presupuestoReferencial = floatval($productoSeleccionadoObj->presupuesto ?? 0);
                                    $presupuestoReal = $productoSeleccionadoObj->actividades->sum('monto');
                                    $gastoProducto = $productoSeleccionadoObj->actividades->sum('gasto_acumulado');
                                    $saldoProducto = $presupuestoReal - $gastoProducto;
                                    $progresoProducto = $productoSeleccionadoObj->actividades->avg('progreso') ?? 0;
                                    $diferenciaPresupuestos = $presupuestoReal - $presupuestoReferencial;
                                    $excedePptoReferencial = $diferenciaPresupuestos > 0;
                                @endphp

                                    <!-- Panel Compacto de Resumen -->
                                <div class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 border-slate-200 dark:border-slate-700 p-6 shadow-sm">

                                    <!-- Fila 1: Presupuestos -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 pb-4 border-b border-slate-300 dark:border-slate-600">
                                        <!-- Presupuesto Referencial -->
                                        <div class="text-center">
                                            <div class="inline-flex items-center justify-center w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg mb-2">
                                                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </div>
                                            <p class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Presupuesto Referencial</p>
                                            <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400">
                                                ${{ number_format($presupuestoReferencial, 0) }}
                                            </p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Producto original</p>
                                        </div>

                                        <!-- Presupuesto Real -->
                                        <div class="text-center">
                                            <div class="inline-flex items-center justify-center w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg mb-2">
                                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                            <p class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Presupuesto Real</p>
                                            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                                ${{ number_format($presupuestoReal, 0) }}
                                            </p>
                                            @if($excedePptoReferencial)
                                                <p class="text-xs text-red-600 dark:text-red-400 font-semibold mt-1">
                                                    ⚠️ +${{ number_format($diferenciaPresupuestos, 0) }}
                                                </p>
                                            @elseif($diferenciaPresupuestos < 0)
                                                <p class="text-xs text-green-600 dark:text-green-400 font-semibold mt-1">
                                                    ✓ -${{ number_format(abs($diferenciaPresupuestos), 0) }}
                                                </p>
                                            @else
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Suma de actividades</p>
                                            @endif
                                        </div>

                                        <!-- Ejecutado -->
                                        <div class="text-center">
                                            <div class="inline-flex items-center justify-center w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg mb-2">
                                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </div>
                                            <p class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Ejecutado</p>
                                            <p class="text-xl font-bold text-green-600 dark:text-green-400">
                                                ${{ number_format($gastoProducto, 0) }}
                                            </p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                {{ $presupuestoReal > 0 ? round(($gastoProducto / $presupuestoReal) * 100) : 0 }}% del total
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Fila 2: Fechas y Métricas -->
                                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                        <!-- Fecha Inicio -->
                                        <div class="text-center">
                                            <div class="inline-flex items-center justify-center w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg mb-2">
                                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Inicio</p>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">
                                                {{ $productoSeleccionadoObj->fecha_inicio ? \Carbon\Carbon::parse($productoSeleccionadoObj->fecha_inicio)->format('d/m/Y') : '-' }}
                                            </p>
                                        </div>

                                        <!-- Fecha Fin -->
                                        <div class="text-center">
                                            <div class="inline-flex items-center justify-center w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg mb-2">
                                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Fin</p>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">
                                                {{ $productoSeleccionadoObj->fecha_fin ? \Carbon\Carbon::parse($productoSeleccionadoObj->fecha_fin)->format('d/m/Y') : '-' }}
                                            </p>
                                        </div>

                                        <!-- Saldo -->
                                        <div class="text-center">
                                            <div class="inline-flex items-center justify-center w-8 h-8 {{ $saldoProducto < 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-cyan-100 dark:bg-cyan-900/30' }} rounded-lg mb-2">
                                                <svg class="w-4 h-4 {{ $saldoProducto < 0 ? 'text-red-600 dark:text-red-400' : 'text-cyan-600 dark:text-cyan-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </div>
                                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Saldo</p>
                                            <p class="text-sm font-bold {{ $saldoProducto < 0 ? 'text-red-600 dark:text-red-400' : 'text-cyan-600 dark:text-cyan-400' }}">
                                                ${{ number_format(abs($saldoProducto), 0) }}
                                            </p>
                                        </div>

                                        <!-- Actividades -->
                                        <div class="text-center">
                                            <div class="inline-flex items-center justify-center w-8 h-8 bg-orange-100 dark:bg-orange-900/30 rounded-lg mb-2">
                                                <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                </svg>
                                            </div>
                                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Total</p>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">
                                                {{ $productoSeleccionadoObj->actividades->count() }}
                                            </p>
                                        </div>

                                        <!-- Progreso -->
                                        <div class="text-center">
                                            <div class="inline-flex items-center justify-center w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg mb-2">
                                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                                </svg>
                                            </div>
                                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Progreso</p>
                                            <p class="text-sm font-bold text-purple-600 dark:text-purple-400">
                                                {{ round($progresoProducto) }}%
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Alerta si excede presupuesto -->
                                    @if($excedePptoReferencial && $diferenciaPresupuestos > 0)
                                        <div class="mt-4 pt-4 border-t border-slate-300 dark:border-slate-600">
                                            <div class="flex items-center gap-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg px-3 py-2">
                                                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                <p class="text-xs font-semibold text-red-800 dark:text-red-200">
                                                    Las actividades exceden el presupuesto referencial del producto por <strong>${{ number_format($diferenciaPresupuestos, 2) }}</strong>
                                                </p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Buscador de Actividades -->
                            <div class="mb-6">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        wire:model.live.debounce.300ms="searchActividades"
                                        placeholder="Buscar actividades..."
                                        class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    @if($searchActividades)
                                        <button
                                            wire:click="$set('searchActividades', '')"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                            <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Grid de Actividades -->
                            @if($actividadesProductoSeleccionado && $actividadesProductoSeleccionado->isEmpty())
                                <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                                    @if($searchActividades)
                                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No se encontraron actividades</h4>
                                        <p class="text-gray-600 dark:text-gray-400 mb-4">No hay actividades que coincidan con "{{ $searchActividades }}"</p>
                                        <button wire:click="$set('searchActividades', '')"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                                            Limpiar búsqueda
                                        </button>
                                    @else
                                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Sin actividades</h4>
                                        <p class="text-gray-600 dark:text-gray-400 mb-4">Este producto aún no tiene actividades registradas</p>
                                        <button wire:click="openActivityModal({{ $productoSeleccionadoObj->id }})"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Crear Primera Actividad
                                        </button>
                                    @endif
                                </div>
                            @elseif($actividadesProductoSeleccionado)
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                                    @foreach($actividadesProductoSeleccionado as $actividad)
                                        @php
                                            $saldoActividad = $actividad->monto - $actividad->gasto_acumulado;
                                            $estadoClass = match($actividad->estado) {
                                                'finalizado' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                'en_curso' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                'atrasado' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                            };
                                        @endphp

                                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg transition-shadow">
                                            <!-- Header -->
                                            <div class="flex items-start justify-between mb-3">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-2">
                                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">
                                                    #{{ $actividad->id }}
                                                </span>
                                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $estadoClass }}">
                                                    {{ ucfirst($actividad->estado) }}
                                                </span>
                                                    </div>
                                                    <h5 class="font-semibold text-gray-900 dark:text-white text-sm line-clamp-2 mb-2">
                                                        {{ $actividad->nombre }}
                                                    </h5>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">
                                                        {{ $actividad->descripcion }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Métricas -->
                                            <div class="grid grid-cols-2 gap-2 mb-3">
                                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded p-2">
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">Presupuesto</p>
                                                    <p class="text-sm font-bold text-gray-900 dark:text-white">${{ number_format($actividad->monto, 0) }}</p>
                                                </div>
                                                <div class="bg-green-50 dark:bg-green-900/20 rounded p-2">
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">Ejecutado</p>
                                                    <p class="text-sm font-bold text-green-600 dark:text-green-400">${{ number_format($actividad->gasto_acumulado, 0) }}</p>
                                                </div>
                                            </div>

                                            <!-- Progreso -->
                                            <div class="mb-3">
                                                <div class="flex items-center justify-between text-xs mb-1">
                                                    <span class="text-gray-600 dark:text-gray-400">Progreso</span>
                                                    <span class="font-bold text-purple-600 dark:text-purple-400">{{ $actividad->progreso }}%</span>
                                                </div>
                                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                    <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $actividad->progreso }}%"></div>
                                                </div>
                                            </div>

                                            @if($saldoActividad < 0)
                                                <div class="mb-3 bg-red-50 dark:bg-red-900/20 border-l-2 border-red-500 p-2 rounded">
                                                    <p class="text-xs font-semibold text-red-800 dark:text-red-300">
                                                        ⚠️ Excedido ${{ number_format(abs($saldoActividad), 0) }}
                                                    </p>
                                                </div>
                                            @endif

                                            <!-- Botones -->
                                            <div class="flex gap-2">
                                                <a href="{{ route('actividades.seguimiento', $actividad->id) }}"
                                                   class="flex-1 bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-lg text-xs font-medium transition-colors text-center">
                                                    📊 Seguimiento
                                                </a>
                                                <a href="{{ route('actividades.historial', $actividad->id) }}"
                                                   wire:navigate
                                                   class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs font-medium text-center transition-colors">
                                                    📜 Historial
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Paginación de Actividades -->
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                    {{ $actividadesProductoSeleccionado->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Footer con ayuda -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        💡 <span class="font-medium">Haz clic en cualquier producto</span> para ver sus actividades detalladas
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Nuevo Producto -->
    <div x-data="{ show: @entangle('showProductModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Nuevo Producto</h2>
                    <button wire:click="$set('showProductModal', false)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form wire:submit="createProducto" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre del Producto
                        *</label>
                    <input wire:model="producto_nombre" type="text"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    @error('producto_nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción *</label>
                    <textarea wire:model="producto_descripcion" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                    @error('producto_descripcion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Presupuesto (USD)
                            *</label>
                        <input wire:model="producto_presupuesto" type="number" step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('producto_presupuesto') <span
                            class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div></div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Inicio
                            *</label>
                        <input wire:model="producto_fecha_inicio" type="date"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('producto_fecha_inicio') <span
                            class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Fin
                            *</label>
                        <input wire:model="producto_fecha_fin" type="date"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('producto_fecha_fin') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" wire:click="$set('showProductModal', false)"
                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold">
                        Crear Producto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Nueva Actividad -->
    <div x-data="{ show: @entangle('showActivityModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Nueva Actividad</h2>
                    <button wire:click="$set('showActivityModal', false)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form wire:submit="createActividad" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre de la
                        Actividad *</label>
                    <input wire:model="actividad_nombre" type="text"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    @error('actividad_nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción *</label>
                    <textarea wire:model="actividad_descripcion" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                    @error('actividad_descripcion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Presupuesto (USD)
                            *</label>
                        <input wire:model="actividad_presupuesto" type="number" step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('actividad_presupuesto') <span
                            class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Línea
                            Presupuestaria *</label>
                        <select wire:model="actividad_linea_presupuestaria"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="">Seleccionar...</option>
                            <option value="Consultoría">Consultoría</option>
                            <option value="Equipamiento">Equipamiento</option>
                            <option value="Logística">Logística</option>
                            <option value="Capacitación">Capacitación</option>
                            <option value="Recursos Humanos">Recursos Humanos</option>
                        </select>
                        @error('actividad_linea_presupuestaria') <span
                            class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Inicio
                            *</label>
                        <input wire:model="actividad_fecha_inicio" type="date"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('actividad_fecha_inicio') <span
                            class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Fin
                            *</label>
                        <input wire:model="actividad_fecha_fin" type="date"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('actividad_fecha_fin') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" wire:click="$set('showActivityModal', false)"
                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                        Crear Actividad
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Colaboradores -->
    <div x-data="{ show: @entangle('showCollaboratorsModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Invitar Colaboradores</h2>
                    <button wire:click="$set('showCollaboratorsModal', false)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form wire:submit="inviteCollaborator" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email del Colaborador
                        *</label>
                    <input wire:model="colaborador_email" type="email"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    @error('colaborador_email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono/WhatsApp
                        (Opcional)</label>
                    <input wire:model="colaborador_telefono" type="text"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mensaje
                        Personalizado</label>
                    <textarea wire:model="colaborador_mensaje" rows="4"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                              placeholder="Mensaje opcional para incluir en la invitación..."></textarea>
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" wire:click="$set('showCollaboratorsModal', false)"
                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                        Enviar Invitación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Seguimiento -->
    <div x-data="{ show: @entangle('showSeguimientoModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Registrar Seguimiento</h2>
                        @if($selectedActividad)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $selectedActividad->nombre }}</p>
                        @endif
                    </div>
                    <button wire:click="$set('showSeguimientoModal', false)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            @if($selectedActividad)
                <div class="p-6">
                    <!-- Resumen de la actividad -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">Presupuesto Total</p>
                                <p class="font-bold text-gray-900 dark:text-white">
                                    ${{ number_format($selectedActividad->monto, 2) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">Ejecutado Acumulado</p>
                                <p class="font-bold text-green-600 dark:text-green-400">
                                    ${{ number_format($selectedActividad->gasto_acumulado, 2) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">Saldo Disponible</p>
                                <p class="font-bold {{ ($selectedActividad->monto - $selectedActividad->gasto_acumulado) < 0 ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400' }}">
                                    ${{ number_format($selectedActividad->monto - $selectedActividad->gasto_acumulado, 2) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">Progreso Actual</p>
                                <p class="font-bold text-purple-600 dark:text-purple-400">{{ $selectedActividad->progreso }}
                                    %</p>
                            </div>
                        </div>
                    </div>

                    <form wire:submit="registrarSeguimiento" class="space-y-6" x-data="{ tab: 'progreso' }">

                        <!-- Tabs de secciones -->
                        <div class="border-b border-gray-200 dark:border-gray-700">
                            <nav class="-mb-px flex space-x-4">
                                <button type="button" @click="tab = 'progreso'"
                                        :class="tab === 'progreso' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                    📊 Progreso y Presupuesto
                                </button>
                                <button type="button" @click="tab = 'detalles'"
                                        :class="tab === 'detalles' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                    📝 Detalles
                                </button>
                                <button type="button" @click="tab = 'riesgos'"
                                        :class="tab === 'riesgos' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                    ⚠️ Riesgos
                                </button>
                                <button type="button" @click="tab = 'evidencia'"
                                        :class="tab === 'evidencia' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                    📎 Evidencia
                                </button>
                            </nav>
                        </div>

                        <!-- Tab 1: Progreso y Presupuesto -->
                        <div x-show="tab === 'progreso'" x-cloak class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Nuevo Progreso (%) *
                                    </label>
                                    <input wire:model.live.number="nuevo_progreso" type="number"
                                           min="{{ $selectedActividad->progreso }}" max="100" step="1"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                    @error('nuevo_progreso')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                    @enderror
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Actual: {{ $selectedActividad->progreso }}%
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Monto Gastado Esta Vez (USD) *
                                    </label>
                                    <input wire:model.live.number="nuevo_gasto" type="number" min="0" step="0.01"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                    @error('nuevo_gasto')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                    @enderror

                                    @php
                                        $nuevoSaldo = $this->getNuevoSaldoCalculado();
                                        $excedePpto = $this->getExcedePresupuestoCalculado();
                                    @endphp

                                    <p class="text-xs mt-1 {{ $excedePpto ? 'text-red-600 dark:text-red-400 font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                                        @if($excedePpto)
                                            ⚠️ Excede presupuesto por ${{ number_format(abs($nuevoSaldo), 2) }}
                                        @else
                                            Saldo después: ${{ number_format($nuevoSaldo, 2) }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <!-- Alerta si excede presupuesto -->
                            @if($excedePpto && $nuevo_gasto > 0)
                                <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 mr-3"
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                  d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                        <div class="flex-1">
                                            <h4 class="text-sm font-bold text-red-800 dark:text-red-200">⚠️ ALERTA: Sobrepresupuesto</h4>
                                            <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                                                Este gasto excederá el presupuesto en <span class="font-bold">${{ number_format(abs($nuevoSaldo), 2) }}</span>.
                                                Se requiere justificación y aprobación especial.
                                            </p>
                                            <div class="mt-3">
                                                <label class="block text-sm font-medium text-red-800 dark:text-red-200 mb-1">
                                                    Justificación del Sobregiro *
                                                </label>
                                                <textarea wire:model="justificacion_sobregiro" rows="2"
                                                          class="w-full px-3 py-2 border border-red-300 dark:border-red-600 rounded-lg focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                                          placeholder="Explique por qué es necesario exceder el presupuesto..."></textarea>
                                                @error('justificacion_sobregiro')
                                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Indicador de eficiencia -->
                            @if($nuevo_progreso > 0 && $nuevo_gasto > 0)
                                @php
                                    // Convertir todos los valores a números
                                    $nuevoProgresoNum = floatval($nuevo_progreso ?? 0);
                                    $progresoActualNum = floatval($selectedActividad->progreso ?? 0);
                                    $nuevoGastoNum = floatval($nuevo_gasto ?? 0);
                                    $gastoAcumuladoNum = floatval($selectedActividad->gasto_acumulado ?? 0);
                                    $montoNum = floatval($selectedActividad->monto ?? 1);

                                    // Cálculos
                                    $progresoIncremento = $nuevoProgresoNum - $progresoActualNum;
                                    $gastoAcumuladoNuevo = $gastoAcumuladoNum + $nuevoGastoNum;

                                    // Calcular porcentaje de gasto
                                    $porcentajeGasto = $montoNum > 0 ? ($gastoAcumuladoNuevo / $montoNum) * 100 : 0;

                                    // Calcular índice de eficiencia de manera segura
                                    if ($gastoAcumuladoNuevo > 0 && $montoNum > 0 && $nuevoProgresoNum > 0) {
                                        $indiceEficiencia = $nuevoProgresoNum / ((($gastoAcumuladoNuevo / $montoNum) * 100));
                                    } else {
                                        $indiceEficiencia = 0;
                                    }

                                    // Determinar color según eficiencia
                                    if ($indiceEficiencia >= 1) {
                                        $eficienteColor = 'green';
                                        $eficienteTexto = '✅ Eficiente (Progreso ≥ Gasto)';
                                    } elseif ($indiceEficiencia >= 0.8) {
                                        $eficienteColor = 'yellow';
                                        $eficienteTexto = '⚠️ Aceptable';
                                    } else {
                                        $eficienteColor = 'red';
                                        $eficienteTexto = '🔴 Ineficiente (Gasto > Progreso)';
                                    }
                                @endphp

                                <div class="bg-{{ $eficienteColor }}-50 dark:bg-{{ $eficienteColor }}-900/20 border border-{{ $eficienteColor }}-200 dark:border-{{ $eficienteColor }}-700 rounded-lg p-3">
                                    <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-{{ $eficienteColor }}-800 dark:text-{{ $eficienteColor }}-200">
                        Índice de Eficiencia: {{ number_format($indiceEficiencia, 2) }}
                    </span>
                                        <span class="text-xs text-{{ $eficienteColor }}-600 dark:text-{{ $eficienteColor }}-400">
                        {{ $eficienteTexto }}
                    </span>
                                    </div>
                                    <div class="mt-2 text-xs text-{{ $eficienteColor }}-700 dark:text-{{ $eficienteColor }}-300">
                                        <p>Progreso: {{ number_format($nuevoProgresoNum, 1) }}% | Gasto: {{ number_format($porcentajeGasto, 1) }}%</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Tab 2: Detalles -->
                        <div x-show="tab === 'detalles'" x-cloak class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Descripción del Avance *
                                </label>
                                <textarea wire:model="descripcion_avance" rows="4"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          placeholder="Describa los avances realizados..."></textarea>
                                @error('descripcion_avance')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Responsable del Avance *
                                </label>
                                <input wire:model="responsable_avance" type="text"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                       placeholder="Nombre del responsable">
                                @error('responsable_avance')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Logros Alcanzados
                                </label>
                                <textarea wire:model="logros" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          placeholder="¿Qué se logró en este periodo?"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Dificultades Encontradas
                                </label>
                                <textarea wire:model="dificultades" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          placeholder="¿Qué obstáculos se presentaron?"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Próximos Pasos
                                </label>
                                <textarea wire:model="proximos_pasos" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          placeholder="¿Qué se planea hacer a continuación?"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Próxima Fecha de Revisión
                                </label>
                                <input wire:model="proxima_revision" type="date"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        <!-- Tab 3: Riesgos -->
                        <div x-show="tab === 'riesgos'" x-cloak class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nivel de Riesgo
                                </label>
                                <select wire:model="nivel_riesgo"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                    <option value="bajo">🟢 Bajo - Todo en orden</option>
                                    <option value="medio">🟡 Medio - Requiere atención</option>
                                    <option value="alto">🟠 Alto - Necesita acción inmediata</option>
                                    <option value="critico">🔴 Crítico - Riesgo severo</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Riesgos Identificados
                                </label>
                                <textarea wire:model="riesgos_identificados" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          placeholder="Liste los riesgos detectados..."></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Acciones Correctivas Propuestas
                                </label>
                                <textarea wire:model="acciones_correctivas" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          placeholder="¿Qué acciones se tomarán para mitigar los riesgos?"></textarea>
                            </div>

                            <!-- Etiquetas -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Etiquetas (Tags)
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(['urgente', 'requiere_revision', 'en_riesgo', 'cambio_alcance', 'retraso', 'sobrepresupuesto'] as $tag)
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" wire:model="etiquetas" value="{{ $tag }}"
                                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $tag)) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Tab 4: Evidencia -->
                        <div x-show="tab === 'evidencia'" x-cloak class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Archivos Adjuntos
                                </label>
                                <input wire:model="archivos" type="file" multiple accept=".pdf,.doc,.docx,.xls,.xlsx"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">PDF, Word, Excel - Máx. 10MB por archivo</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Imágenes/Fotos
                                </label>
                                <input wire:model="imagenes" type="file" multiple accept="image/*"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">JPG, PNG - Máx. 5MB por imagen</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Observaciones Adicionales
                                </label>
                                <textarea wire:model="observaciones" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          placeholder="Cualquier información adicional relevante..."></textarea>
                            </div>
                        </div>

                        <!-- Resumen Final -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-900 dark:text-blue-200 mb-2">📋 Resumen del Registro</h4>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-blue-700 dark:text-blue-300">Progreso:</span>
                                    <span class="font-bold text-blue-900 dark:text-blue-100">
                    {{ number_format($selectedActividad->progreso ?? 0, 1) }}% → {{ number_format($nuevo_progreso ?? 0, 1) }}%
                </span>
                                </div>
                                <div>
                                    <span class="text-blue-700 dark:text-blue-300">Gasto Acumulado:</span>
                                    <span class="font-bold text-blue-900 dark:text-blue-100">
                    @php
                        $gastoTotalCalculado = floatval($selectedActividad->gasto_acumulado ?? 0) + floatval($nuevo_gasto ?? 0);
                    @endphp
                    ${{ number_format($gastoTotalCalculado, 2) }}
                </span>
                                </div>
                                <div>
                                    <span class="text-blue-700 dark:text-blue-300">Gasto Esta Vez:</span>
                                    <span class="font-bold text-green-600 dark:text-green-400">
                    ${{ number_format(floatval($nuevo_gasto ?? 0), 2) }}
                </span>
                                </div>
                                <div>
                                    <span class="text-blue-700 dark:text-blue-300">Saldo Resultante:</span>
                                    <span class="font-bold {{ $this->getExcedePresupuestoCalculado() ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400' }}">
                    ${{ number_format(abs($this->getNuevoSaldoCalculado()), 2) }}
                                        @if($this->getExcedePresupuestoCalculado())
                                            <span class="text-xs">(Exceso)</span>
                                        @endif
                </span>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" wire:click="$set('showSeguimientoModal', false)"
                                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="flex-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold">
                                💾 Guardar Seguimiento
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal de Advertencias Unificado (Montos + Fechas) -->
    <div x-data="{ show: @entangle('showAdvertenciasModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4"
         @click.self="$wire.cancelarCreacionConAdvertencias()">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
            <!-- Header -->
            <div class="sticky top-0 p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-yellow-50 to-red-50 dark:from-yellow-900/20 dark:to-red-900/20 z-10">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <svg class="w-12 h-12 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            ⚠️ Advertencias Detectadas
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Se encontraron inconsistencias que requieren su atención
                        </p>
                    </div>
                    <button wire:click="cancelarCreacionConAdvertencias"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Contenido -->
            <div class="p-6 space-y-6">

                <!-- ═══════════════════════════════════════════ -->
                <!-- ADVERTENCIAS DE MONTOS -->
                <!-- ═══════════════════════════════════════════ -->
                @if(!empty($advertenciasMontos))
                    <div class="space-y-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            💰 Advertencias de Presupuesto
                        </h3>

                        <!-- Advertencia Producto -->
                        @if(isset($advertenciasMontos['producto']))
                            <div class="bg-orange-50 dark:bg-orange-900/20 border-l-4 border-orange-500 rounded-lg p-4">
                                <div class="flex items-start gap-3 mb-3">
                                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <div class="flex-1">
                                        <h4 class="text-base font-bold text-orange-900 dark:text-orange-200 mb-2">
                                            📦 PRODUCTO: {{ $advertenciasMontos['producto']['nombre'] }}
                                        </h4>

                                        <div class="bg-white dark:bg-gray-800 rounded p-3 mb-3">
                                            <div class="grid grid-cols-2 gap-3 text-sm">
                                                <div>
                                                    <p class="text-gray-600 dark:text-gray-400">Presupuesto Asignado</p>
                                                    <p class="font-bold text-gray-900 dark:text-white">
                                                        ${{ number_format($advertenciasMontos['producto']['presupuesto_asignado'], 2) }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-600 dark:text-gray-400">Suma Actual</p>
                                                    <p class="font-bold text-blue-600 dark:text-blue-400">
                                                        ${{ number_format($advertenciasMontos['producto']['suma_actual'], 2) }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-600 dark:text-gray-400">Nueva Actividad</p>
                                                    <p class="font-bold text-purple-600 dark:text-purple-400">
                                                        + ${{ number_format($advertenciasMontos['producto']['monto_nueva'], 2) }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-600 dark:text-gray-400">Nuevo Total</p>
                                                    <p class="font-bold text-orange-600 dark:text-orange-400">
                                                        ${{ number_format($advertenciasMontos['producto']['nuevo_total'], 2) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-orange-100 dark:bg-orange-900/30 rounded p-3">
                                            <p class="text-sm font-bold text-orange-900 dark:text-orange-200">
                                                ⚠️ EXCEDE POR: ${{ number_format($advertenciasMontos['producto']['diferencia'], 2) }}
                                                ({{ $advertenciasMontos['producto']['porcentaje_exceso'] }}% sobre presupuesto)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Advertencia Carta -->
                        @if(isset($advertenciasMontos['carta']))
                            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg p-4">
                                <div class="flex items-start gap-3 mb-3">
                                    <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <div class="flex-1">
                                        <h4 class="text-base font-bold text-red-900 dark:text-red-200 mb-2">
                                            📋 CARTA DOCUMENTO: {{ $carta->codigo }}
                                        </h4>

                                        <div class="bg-white dark:bg-gray-800 rounded p-3 mb-3">
                                            <div class="grid grid-cols-2 gap-3 text-sm">
                                                <div>
                                                    <p class="text-gray-600 dark:text-gray-400">Presupuesto Referencial</p>
                                                    <p class="font-bold text-gray-900 dark:text-white">
                                                        ${{ number_format($advertenciasMontos['carta']['presupuesto_referencial'], 2) }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-600 dark:text-gray-400">Suma Total Actual</p>
                                                    <p class="font-bold text-blue-600 dark:text-blue-400">
                                                        ${{ number_format($advertenciasMontos['carta']['suma_actual'], 2) }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-600 dark:text-gray-400">Nueva Actividad</p>
                                                    <p class="font-bold text-purple-600 dark:text-purple-400">
                                                        + ${{ number_format($advertenciasMontos['carta']['monto_nueva'], 2) }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-600 dark:text-gray-400">Nuevo Total</p>
                                                    <p class="font-bold text-red-600 dark:text-red-400">
                                                        ${{ number_format($advertenciasMontos['carta']['nuevo_total'], 2) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-red-100 dark:bg-red-900/30 rounded p-3">
                                            <p class="text-sm font-bold text-red-900 dark:text-red-200">
                                                🔴 EXCEDE POR: ${{ number_format($advertenciasMontos['carta']['diferencia'], 2) }}
                                                ({{ $advertenciasMontos['carta']['porcentaje_exceso'] }}% sobre presupuesto referencial)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- ═══════════════════════════════════════════ -->
                <!-- ADVERTENCIAS DE FECHAS -->
                <!-- ═══════════════════════════════════════════ -->
                @if(!empty($advertenciasFechas))
                    <div class="space-y-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            📅 Advertencias de Fechas
                        </h3>

                        <!-- Advertencia Producto -->
                        @if(isset($advertenciasFechas['producto']))
                            <div class="bg-orange-50 dark:bg-orange-900/20 border-l-4 border-orange-500 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <div class="flex-1">
                                        <h4 class="text-base font-bold text-orange-900 dark:text-orange-200 mb-2">
                                            📦 PRODUCTO: {{ $advertenciasFechas['producto']['nombre'] }}
                                        </h4>
                                        <div class="bg-white dark:bg-gray-800 rounded p-3 mb-2">
                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                <strong>Rango válido:</strong>
                                                {{ $advertenciasFechas['producto']['fecha_inicio'] }}
                                                →
                                                {{ $advertenciasFechas['producto']['fecha_fin'] }}
                                            </p>
                                        </div>
                                        <ul class="space-y-2 text-sm text-orange-800 dark:text-orange-200">
                                            @foreach($advertenciasFechas['producto']['problemas'] as $problema)
                                                <li class="flex items-start gap-2">
                                                    <span class="text-orange-600 dark:text-orange-400 mt-0.5">•</span>
                                                    <span>{!! $problema !!}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Advertencia Carta -->
                        @if(isset($advertenciasFechas['carta']))
                            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <div class="flex-1">
                                        <h4 class="text-base font-bold text-red-900 dark:text-red-200 mb-2">
                                            📋 CARTA DOCUMENTO: {{ $advertenciasFechas['carta']['codigo'] }}
                                        </h4>
                                        <div class="bg-white dark:bg-gray-800 rounded p-3 mb-2">
                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                <strong>Rango válido:</strong>
                                                {{ $advertenciasFechas['carta']['fecha_inicio'] }}
                                                →
                                                {{ $advertenciasFechas['carta']['fecha_fin'] }}
                                            </p>
                                        </div>
                                        <ul class="space-y-2 text-sm text-red-800 dark:text-red-200">
                                            @foreach($advertenciasFechas['carta']['problemas'] as $problema)
                                                <li class="flex items-start gap-2">
                                                    <span class="text-red-600 dark:text-red-400 mt-0.5">•</span>
                                                    <span>{!! $problema !!}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Recomendación -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-1">
                                💡 Recomendación
                            </h4>
                            <p class="text-sm text-blue-800 dark:text-blue-300">
                                Se recomienda <strong>corregir los valores</strong> antes de continuar. Si decide proceder de todas formas,
                                el sistema registrará estas inconsistencias y podrían requerir justificación posterior.
                            </p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer con Botones -->
            <div class="sticky bottom-0 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-xl">
                <div class="flex gap-3">
                    <button
                        wire:click="cancelarCreacionConAdvertencias"
                        type="button"
                        class="flex-1 px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 font-semibold hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Cancelar y Corregir
                    </button>
                    <button
                        wire:click="confirmarCreacionConAdvertencias"
                        type="button"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-yellow-600 to-red-600 hover:from-yellow-700 hover:to-red-700 text-white rounded-lg font-semibold transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Continuar de Todas Formas
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</div>


