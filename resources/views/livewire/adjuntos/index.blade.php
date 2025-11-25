<?php

use App\Models\Carta;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $filterCarta = '';
    public $filterTipo = '';

    public function with(): array
    {
        $user = auth()->user();

        // Query base de cartas con adjuntos
        $query = Carta::where(function($q) {
            $q->whereNotNull('archivos_adjuntos')
                ->where('archivos_adjuntos', '!=', '[]')
                ->where('archivos_adjuntos', '!=', 'null');
        })
            ->with(['proveedor', 'creador']);

        // SI ES PROVEEDOR, SOLO SUS CARTAS
        if ($user->hasRole('Proveedor') && $user->proveedor) {
            $query->where('proveedor_id', $user->proveedor->id);
        }

        // Filtros de búsqueda
        if ($this->search) {
            $query->where(function($q) {
                $q->where('codigo', 'like', '%' . $this->search . '%')
                    ->orWhere('nombre_proyecto', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterCarta) {
            $query->where('id', $this->filterCarta);
        }

        if ($this->filterTipo) {
            $query->whereRaw("JSON_SEARCH(archivos_adjuntos, 'one', '%.{$this->filterTipo}%') IS NOT NULL");
        }

        $adjuntos = $query->orderBy('created_at', 'desc')->paginate(15);

        // Procesar adjuntos
        // Procesar adjuntos
        $adjuntos->getCollection()->transform(function($carta) {
            // Decodificar JSON de archivos adjuntos
            $archivosData = is_string($carta->archivos_adjuntos)
                ? json_decode($carta->archivos_adjuntos, true)
                : $carta->archivos_adjuntos;

            $archivos = [];

            if (is_array($archivosData)) {
                foreach ($archivosData as $archivo) {
                    if (!isset($archivo['path'])) continue;

                    // IMPORTANTE: El path en DB ya incluye 'cartas/adjuntos/31/...'
                    $pathEnStorage = $archivo['path'];

                    // Verificar si el archivo existe
                    $existe = Storage::disk('public')->exists($pathEnStorage);

                    // Si no existe, intentar sin el prefijo 'cartas/'
                    if (!$existe && str_starts_with($pathEnStorage, 'cartas/')) {
                        $pathAlterno = str_replace('cartas/', '', $pathEnStorage);
                        $existe = Storage::disk('public')->exists($pathAlterno);
                        if ($existe) {
                            $pathEnStorage = $pathAlterno;
                        }
                    }

                    $nombreOriginal = $archivo['nombre_original'] ?? basename($pathEnStorage);
                    $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);

                    $archivos[] = [
                        'nombre' => $nombreOriginal,
                        'nombre_guardado' => $archivo['nombre_guardado'] ?? basename($pathEnStorage),
                        'path' => $pathEnStorage,
                        'url' => Storage::disk('public')->url($pathEnStorage),
                        'extension' => strtolower($extension),
                        'tamaño' => $archivo['size'] ?? (Storage::disk('public')->exists($pathEnStorage) ? Storage::disk('public')->size($pathEnStorage) : 0),
                        'mime_type' => $archivo['mime_type'] ?? '',
                        'fecha' => Storage::disk('public')->exists($pathEnStorage) ? Storage::disk('public')->lastModified($pathEnStorage) : null,
                        'existe' => $existe,
                    ];
                }
            }

            return [
                'carta_id' => $carta->id,
                'codigo' => $carta->codigo,
                'nombre_proyecto' => $carta->nombre_proyecto,
                'proveedor' => $carta->proveedor ? $carta->proveedor->nombre : 'Sin proveedor',
                'estado' => $carta->estado,
                'archivos' => $archivos,
                'fecha_creacion' => $carta->created_at,
                'creado_por' => $carta->creador ? $carta->creador->name : 'Sistema',
            ];
        });

        // Cartas disponibles para filtro
        $cartasQuery = Carta::select('id', 'codigo', 'nombre_proyecto')
            ->where(function($q) {
                $q->whereNotNull('archivos_adjuntos')
                    ->where('archivos_adjuntos', '!=', '[]')
                    ->where('archivos_adjuntos', '!=', 'null');
            });

        if ($user->hasRole('Proveedor') && $user->proveedor) {
            $cartasQuery->where('proveedor_id', $user->proveedor->id);
        }

        $cartas = $cartasQuery->get();

        // Estadísticas
        $statsQuery = Carta::where(function($q) {
            $q->whereNotNull('archivos_adjuntos')
                ->where('archivos_adjuntos', '!=', '[]')
                ->where('archivos_adjuntos', '!=', 'null');
        });

        if ($user->hasRole('Proveedor') && $user->proveedor) {
            $statsQuery->where('proveedor_id', $user->proveedor->id);
        }

        $totalAdjuntos = $statsQuery->count();

        $cartasConAdjuntos = $statsQuery->get();
        $totalArchivos = $cartasConAdjuntos->sum(function($carta) {
            $archivos = is_string($carta->archivos_adjuntos)
                ? json_decode($carta->archivos_adjuntos, true)
                : $carta->archivos_adjuntos;
            return is_array($archivos) ? count($archivos) : 0;
        });

        return [
            'adjuntos' => $adjuntos,
            'cartas' => $cartas,
            'stats' => [
                'total_cartas' => $totalAdjuntos,
                'total_archivos' => $totalArchivos,
            ]
        ];
    }

    public function descargarArchivo($cartaId, $archivoPath)
    {
        $user = auth()->user();

        // Verificar permisos
        $carta = Carta::findOrFail($cartaId);

        if ($user->hasRole('Proveedor') && $user->proveedor) {
            if ($carta->proveedor_id !== $user->proveedor->id) {
                session()->flash('error', 'No tienes permiso para descargar este archivo');
                return;
            }
        }

        // Intentar con el path original
        if (Storage::disk('public')->exists($archivoPath)) {
            return Storage::disk('public')->download($archivoPath);
        }

        // Intentar sin el prefijo 'cartas/'
        if (str_starts_with($archivoPath, 'cartas/')) {
            $pathAlterno = str_replace('cartas/', '', $archivoPath);
            if (Storage::disk('public')->exists($pathAlterno)) {
                return Storage::disk('public')->download($pathAlterno);
            }
        }

        session()->flash('error', 'Archivo no encontrado en: ' . $archivoPath);
    }

    public function getIconoArchivo($extension)
    {
        return match(strtolower($extension)) {
            'pdf' => '📄',
            'doc', 'docx' => '📝',
            'xls', 'xlsx' => '📊',
            'jpg', 'jpeg', 'png', 'gif' => '🖼️',
            'zip', 'rar' => '📦',
            default => '📎',
        };
    }

    public function getColorExtension($extension)
    {
        return match(strtolower($extension)) {
            'pdf' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            'doc', 'docx' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            'xls', 'xlsx' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            'jpg', 'jpeg', 'png', 'gif' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
            'zip', 'rar' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    public function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterCarta()
    {
        $this->resetPage();
    }

    public function updatingFilterTipo()
    {
        $this->resetPage();
    }

    public function debugArchivo($cartaId)
    {
        $carta = Carta::find($cartaId);
        $archivos = json_decode($carta->archivos_adjuntos, true);

        dd([
            'archivos_adjuntos' => $archivos,
            'storage_path' => storage_path('app/public/'),
            'public_path' => public_path('storage/'),
            'existe_storage_link' => is_link(public_path('storage')),
            'disk_public_path' => Storage::disk('public')->path(''),
            'verificaciones' => collect($archivos)->map(function($archivo) {
                $path = $archivo['path'];
                return [
                    'path_original' => $path,
                    'existe_disk_public' => Storage::disk('public')->exists($path),
                    'existe_storage_app' => Storage::exists($path),
                    'path_completo_public' => Storage::disk('public')->path($path),
                    'path_completo_storage' => Storage::path($path),
                    'file_exists_public' => file_exists(Storage::disk('public')->path($path)),
                ];
            })
        ]);
    }
}; ?>

<div title="Adjuntos">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                        </div>
                        Archivos Adjuntos
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Gestión de documentos y archivos adjuntos</p>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Cartas con Adjuntos</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['total_cartas'] }}</p>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Archivos</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['total_archivos'] }}</p>
                    </div>
                    <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por código o proyecto..."
                    class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                />

                <select
                    wire:model.live="filterCarta"
                    class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="">Todas las cartas</option>
                    @foreach($cartas as $carta)
                        <option value="{{ $carta->id }}">{{ $carta->codigo }}</option>
                    @endforeach
                </select>

                <select
                    wire:model.live="filterTipo"
                    class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="">Todos los tipos</option>
                    <option value="pdf">PDF</option>
                    <option value="doc">Word</option>
                    <option value="docx">Word (DOCX)</option>
                    <option value="xls">Excel</option>
                    <option value="xlsx">Excel (XLSX)</option>
                    <option value="jpg">Imagen (JPG)</option>
                    <option value="png">Imagen (PNG)</option>
                    <option value="zip">Comprimido (ZIP)</option>
                </select>

                <button
                    wire:click="$set('search', '')"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition"
                >
                    Limpiar Filtros
                </button>
            </div>
        </div>

        <!-- Listado de Adjuntos -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Carta</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Proveedor</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Archivos</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Estado</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Fecha</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Acciones</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($adjuntos as $adjunto)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $adjunto['codigo'] }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                    {{ $adjunto['nombre_proyecto'] }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                {{ $adjunto['proveedor'] }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-2">
                                    @forelse($adjunto['archivos'] as $archivo)
                                        <div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                            <span class="text-2xl">{{ $this->getIconoArchivo($archivo['extension']) }}</span>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                    {{ $archivo['nombre'] }}
                                                </div>
                                                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <span class="px-2 py-0.5 rounded {{ $this->getColorExtension($archivo['extension']) }}">
                            {{ strtoupper($archivo['extension']) }}
                        </span>
                                                    <span>{{ $this->formatBytes($archivo['tamaño']) }}</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                @if($archivo['existe'])
                                                    <!-- Botón Descargar (icono) -->
                                                    <button
                                                        wire:click="descargarArchivo({{ $adjunto['carta_id'] }}, '{{ $archivo['path'] }}')"
                                                        class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors shadow-sm"
                                                        title="Descargar">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                        </svg>
                                                    </button>

                                                    <!-- Botón Ver (icono) -->
                                                    <a
                                                        href="{{ Storage::url($archivo['path']) }}"
                                                        target="_blank"
                                                        class="p-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors shadow-sm"
                                                        title="Ver en nueva pestaña">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                        </svg>
                                                    </a>
                                                @else
                                                    <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs rounded">
                            ❌
                        </span>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <span class="text-sm text-gray-500">Sin archivos</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium
                                        @if($adjunto['estado'] === 'enviada') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                        @elseif($adjunto['estado'] === 'aceptada') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                        @elseif($adjunto['estado'] === 'en_ejecucion') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                        @elseif($adjunto['estado'] === 'finalizada') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $adjunto['estado'])) }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $adjunto['fecha_creacion']->format('d/m/Y H:i') }}
                                <div class="text-xs text-gray-500">{{ $adjunto['creado_por'] }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('cartas.show', $adjunto['carta_id']) }}"
                                   wire:navigate
                                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Ver Carta
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                No se encontraron archivos adjuntos
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $adjuntos->links() }}
            </div>
        </div>
    </div>
</div>
