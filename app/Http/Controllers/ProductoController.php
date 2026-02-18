<?php
namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Proveedor;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class ProductoController extends Controller
{

    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $perPage = $request->get('per_page', 10);
            $productos = Producto::with(['categoria', 'proveedor'])
                ->orderBy('producto_id', 'desc')
                ->paginate($perPage);
            return response()->json($productos);
        }

        return $this->inventory($request);
    }


    public function store(StoreProductoRequest $request)
    {
        try {
            // Transacción para crear producto
            $producto = DB::transaction(function () use ($request) {
                $data = $request->validated();

                if ($request->hasFile('imagen')) {
                    $imageName = time().'.'.$request->imagen->extension();
                    $request->imagen->move(public_path('assets/images/products'), $imageName);
                    $data['imagen'] = $imageName;
                }

                return Producto::create($data);
            });

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Producto creado exitosamente',
                    'data' => $producto->load(['categoria', 'proveedor'])
                ]);
            }

            return redirect()->route('inventory')->with('status','Producto creado exitosamente');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $errors,
                    'message' => 'Por favor corrige los errores en el formulario',
                ], 422);
            }
            return redirect()->back()->withErrors($errors)->withInput();
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo crear el producto. Inténtalo de nuevo.',
                ], 500);
            }
            return redirect()->back()->with('error', 'No se pudo crear el producto. Inténtalo de nuevo.')->withInput();
        }
    }

    public function show($id)
    {
        try {
            $producto = Producto::with(['categoria','proveedor'])->findOrFail($id);
            
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $producto
                ]);
            }
            
            return view('productos.show', compact('producto'));
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado',
                    'error' => $e->getMessage()
                ], 404);
            }
            
            return redirect()->route('inventory')->with('error', 'Producto no encontrado');
        }
    }

    public function update(UpdateProductoRequest $request, $id)
    {
        try {
            $producto = DB::transaction(function() use ($request, $id) {
                $p = Producto::findOrFail($id);
                $data = $request->validated();

                if ($request->hasFile('imagen')) {
                    // Eliminar imagen anterior si existe
                    if ($p->imagen && file_exists(public_path('assets/images/products/' . $p->imagen))) {
                        unlink(public_path('assets/images/products/' . $p->imagen));
                    }
                    $imageName = time().'.'.$request->imagen->extension();
                    $request->imagen->move(public_path('assets/images/products'), $imageName);
                    $data['imagen'] = $imageName;
                }

                $p->update($data);
                return $p;
            });

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Producto actualizado exitosamente',
                    'data' => $producto->load(['categoria', 'proveedor'])
                ]);
            }

            return redirect()->route('inventory')->with('status','Producto actualizado exitosamente');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $errors,
                    'message' => 'Por favor corrige los errores en el formulario',
                ], 422);
            }
            return redirect()->back()->withErrors($errors)->withInput();
        } catch (\Throwable $e) {
            Log::error('Error updating product: ' . $e->getMessage(), [
                'product_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo actualizar el producto. Inténtalo de nuevo.',
                ], 500);
            }
            return redirect()->back()->with('error', 'No se pudo actualizar el producto. Inténtalo de nuevo.')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function() use ($id) {
                $p = Producto::findOrFail($id);
                $p->update(['estado' => 'inactivo']);
            });

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Producto inactivado correctamente',
                ]);
            }
            return redirect()->route('inventory')->with('status','Producto inactivado correctamente');
        } catch (\Throwable $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al inactivar el producto: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Error al inactivar el producto');
        }
    }

    public function forceDelete($id)
    {
        try {
            DB::transaction(function() use ($id) {
                $p = Producto::findOrFail($id);
                $p->delete(); // Eliminación definitiva
            });

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Producto eliminado definitivamente',
                ]);
            }
            return redirect()->route('inventory')->with('status','Producto eliminado definitivamente');
        } catch (\Throwable $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el producto: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Error al eliminar el producto');
        }
    }

    public function create()
    {
        return view('productos.create');
    }

    public function edit($id)
    {
        $producto = Producto::findOrFail($id);
        return view('productos.edit', compact('producto'));
    }

    public function inventory(Request $request)
    {
        $query = Producto::with(['categoria', 'proveedor']);

        // Aplicar filtros
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'in-stock':
                    $query->where('stock_actual', '>', 10);
                    break;
                case 'low':
                    $query->where('stock_actual', '>', 0)->where('stock_actual', '<=', 10);
                    break;
                case 'out':
                    $query->where('stock_actual', 0);
                    break;
            }
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Ordenamiento
        $sort = $request->get('sort', 'producto_id');
        $order = $request->get('order', 'desc');
        $query->orderBy($sort, $order);

        // Paginación
        $perPage = $request->get('per_page', 10);
        $productos = $query->paginate($perPage);

        // Transformar datos para la vista
        $products = $productos->getCollection()->map(function($producto) {
            return (object)[
                'producto_id' => $producto->producto_id,
                'id' => $producto->producto_id, // Mantener compatibilidad
                'nombre' => $producto->nombre,
                'name' => $producto->nombre, // Mantener compatibilidad
                'sku' => 'BK-' . str_pad($producto->producto_id, 3, '0', STR_PAD_LEFT),
                'image' => $producto->imagen ?? 'default.png',
                'category' => (object)['name' => $producto->categoria->nombre ?? 'Sin categoría'],
                'stock' => $producto->stock_actual,
                'stock_status_class' => $producto->stock_actual > 10 ? 'success' : 
                                      ($producto->stock_actual > 0 ? 'warning' : 'danger'),
                'price' => $producto->precio_venta,
                'status' => ucfirst($producto->estado),
                'status_class' => $producto->estado === 'activo' ? 'success' : 
                                ($producto->estado === 'inactivo' ? 'warning' : 'secondary')
            ];
        });

        // Obtener categorías para el filtro
        $categorias = Categoria::orderBy('nombre')->get(['categoria_id', 'nombre']);

        return view('inventory', compact('products', 'productos', 'categorias'));
    }

    public function export(Request $request, $format = null)
    {
        $format = strtolower($format ?? $request->get('format', 'csv'));
    
        $data = Producto::with(['categoria:categoria_id,nombre','proveedor:proveedor_id,nombre'])
            ->orderBy('nombre')
            ->get();
    
        if ($format === 'xml') {
            $xml = new \SimpleXMLElement('<productos/>');
            foreach ($data as $p) {
                $n = $xml->addChild('producto');
                $n->addChild('id', $p->producto_id);
                $n->addChild('nombre', htmlspecialchars($p->nombre));
                $n->addChild('descripcion', htmlspecialchars($p->descripcion ?? ''));
                $n->addChild('categoria', htmlspecialchars(optional($p->categoria)->nombre));
                $n->addChild('proveedor', htmlspecialchars(optional($p->proveedor)->nombre));
                $n->addChild('precio_compra', number_format((float)$p->precio_compra,2,'.',''));
                $n->addChild('precio_venta', number_format((float)$p->precio_venta,2,'.',''));
                $n->addChild('stock_actual', (string)$p->stock_actual);
                $n->addChild('stock_minimo', (string)$p->stock_minimo);
                $n->addChild('estado', $p->estado);
                $n->addChild('fecha_creacion', (string)$p->fecha_creacion);
            }
            $filename = 'productos_'.date('Ymd_His').'.xml';
            return response($xml->asXML(), 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ]);
        }
    
        if ($format === 'json') {
            $payload = $data->map(function($p){
                return [
                    'id' => $p->producto_id,
                    'nombre' => $p->nombre,
                    'descripcion' => $p->descripcion,
                    'categoria' => optional($p->categoria)->nombre,
                    'proveedor' => optional($p->proveedor)->nombre,
                    'precio_compra' => $p->precio_compra,
                    'precio_venta' => $p->precio_venta,
                    'stock_actual' => $p->stock_actual,
                    'stock_minimo' => $p->stock_minimo,
                    'estado' => $p->estado,
                    'fecha_creacion' => $p->fecha_creacion,
                ];
            });
            $filename = 'productos_'.date('Ymd_His').'.json';
            return response()->streamDownload(function() use ($payload){
                // Usar print en lugar de echo para mejor control en closures
                print($payload->toJson(JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            }, $filename, ['Content-Type' => 'application/json; charset=UTF-8']);
        }
        
        if ($format === 'pdf') {
            $filename = 'productos_'.date('Ymd_His').'.pdf';
            
            // Preparar datos para la vista PDF
            $products = $data->map(function($p) {
                return (object)[
                    'id' => $p->producto_id,
                    'nombre' => $p->nombre,
                    'descripcion' => $p->descripcion ?? 'Sin descripción',
                    'categoria' => optional($p->categoria)->nombre ?? 'Sin categoría',
                    'proveedor' => optional($p->proveedor)->nombre ?? 'Sin proveedor',
                    'precio_compra' => number_format((float)$p->precio_compra, 2),
                    'precio_venta' => number_format((float)$p->precio_venta, 2),
                    'stock_actual' => $p->stock_actual,
                    'stock_minimo' => $p->stock_minimo,
                    'estado' => ucfirst($p->estado),
                    'fecha_creacion' => $p->fecha_creacion ? $p->fecha_creacion->format('d/m/Y') : 'N/A',
                ];
            });
            
            $pdf = PDF::loadView('exports.productos-pdf', [
                'products' => $products,
                'total' => $products->count(),
                'fecha_exportacion' => now()->format('d/m/Y H:i:s')
            ]);
            
            return $pdf->download($filename);
        }
    
        // CSV por defecto
        $filename = 'productos_'.date('Ymd_His').'.csv';
        return response()->streamDownload(function() use ($data){
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8
            fputcsv($out, ['ID','Nombre','Descripción','Imagen','Categoría','Proveedor','Precio Compra','Precio Venta','Stock','Mínimo','Estado','Creación']);
            foreach ($data as $p) {
                fputcsv($out, [
                    $p->producto_id, 
                    $p->nombre, 
                    $p->descripcion,
                    $p->imagen ?? '',
                    optional($p->categoria)->nombre, 
                    optional($p->proveedor)->nombre,
                    $p->precio_compra, 
                    $p->precio_venta, 
                    $p->stock_actual, 
                    $p->stock_minimo, 
                    $p->estado,
                    $p->fecha_creacion ? $p->fecha_creacion->format('Y-m-d H:i:s') : ''
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xml,csv,txt,json|max:10240'
        ]);

        $file = $request->file('import_file');
        
        // Detectar automáticamente el formato del archivo
        $format = $this->detectFileFormat($file);
        
        if (!$format) {
            return redirect()->back()->with('error', 'No se pudo detectar el formato del archivo. Formatos soportados: XML, CSV y JSON.');
        }
        
        try {
            switch ($format) {
                case 'xml':
                    return $this->importXml($file);
                case 'csv':
                    return $this->importCsv($file);
                case 'json':
                    return $this->importJson($file);
                default:
                    return redirect()->back()->with('error', 'Formato no válido');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    /**
     * Detecta automáticamente el formato del archivo basándose en la extensión y el contenido
     */
    private function detectFileFormat($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        
        // Primero intentar detectar por extensión
        if (in_array($extension, ['xml'])) {
            return 'xml';
        } elseif (in_array($extension, ['csv', 'txt'])) {
            return 'csv';
        } elseif (in_array($extension, ['json'])) {
            return 'json';
        }
        
        // Si no se detecta por extensión, intentar por contenido
        $content = file_get_contents($file->getPathname());
        $trimmedContent = trim($content);
        
        // Detectar XML por el inicio del contenido
        if (preg_match('/^<\?xml/i', $trimmedContent) || preg_match('/^<[a-zA-Z]/', $trimmedContent)) {
            return 'xml';
        }
        
        // Detectar JSON por el inicio del contenido
        if (preg_match('/^[\s]*[\[\{]/', $trimmedContent)) {
            $decoded = json_decode($trimmedContent, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return 'json';
            }
        }
        
        // Si tiene extensión txt o csv, asumir CSV
        if (in_array($extension, ['txt', 'csv']) || $mimeType === 'text/csv' || $mimeType === 'text/plain') {
            return 'csv';
        }
        
        return null;
    }
    private function importXml($file)
    {
        try {
            $xmlContent = file_get_contents($file->getPathname());
            
            // Validar que el contenido no esté vacío
            if (empty(trim($xmlContent))) {
                throw new \Exception('El archivo XML está vacío o no se pudo leer correctamente.');
            }
            
            // Validar que el contenido sea XML válido
            libxml_use_internal_errors(true);
            $xml = new \SimpleXMLElement($xmlContent);
            $xmlErrors = libxml_get_errors();
            
            if (!empty($xmlErrors)) {
                $errorMessages = array_map(function($error) {
                    return trim($error->message);
                }, $xmlErrors);
                throw new \Exception('Error al parsear XML: ' . implode(', ', $errorMessages));
            }
            
            $importados = 0;
            $errores = [];
        
            DB::beginTransaction();
        
            // Verificar que existan productos en el XML
            if (!isset($xml->producto) || count($xml->producto) == 0) {
                throw new \Exception('No se encontraron productos en el archivo XML.');
            }
        
            foreach ($xml->producto as $productoXml) {
                try {
                    // Validar que los campos requeridos existan
                    $requiredFields = ['nombre', 'categoria', 'proveedor', 'precio_compra', 'precio_venta', 'stock_actual', 'stock_minimo'];
                    foreach ($requiredFields as $field) {
                        if (!isset($productoXml->$field)) {
                            throw new \Exception("Campo requerido '{$field}' no encontrado en el producto.");
                        }
                    }
                    
                    $result = $this->createProductFromData([
                        'nombre' => (string)$productoXml->nombre,
                        'descripcion' => isset($productoXml->descripcion) ? (string)$productoXml->descripcion : '',
                        'categoria' => (string)$productoXml->categoria,
                        'proveedor' => (string)$productoXml->proveedor,
                        'precio_compra' => (float)$productoXml->precio_compra,
                        'precio_venta' => (float)$productoXml->precio_venta,
                        'stock_actual' => (int)$productoXml->stock_actual,
                        'stock_minimo' => (int)$productoXml->stock_minimo,
                        'estado' => isset($productoXml->estado) ? (string)$productoXml->estado : 'activo',
                    ]);
        
                    if ($result['success']) {
                        $importados++;
                    } else {
                        $errores[] = $result['error'];
                    }
                } catch (\Exception $e) {
                    $errores[] = "Error al importar producto: " . $e->getMessage();
                }
            }
        
            // Si se presentaron errores, se revierte toda la importación
            if (!empty($errores)) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        
            return $this->handleImportResult($importados, $errores);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al procesar el archivo XML: ' . $e->getMessage());
        }
    }
    
    private function importCsv($file)
    {
        $csvData = array_map('str_getcsv', file($file->getPathname()));
        $headers = array_shift($csvData);
        $importados = 0;
        $errores = [];
    
        DB::beginTransaction();
    
        foreach ($csvData as $row) {
            try {
                $data = array_combine($headers, $row);
                $result = $this->createProductFromData($data);
    
                if ($result['success']) {
                    $importados++;
                } else {
                    $errores[] = $result['error'];
                }
            } catch (\Exception $e) {
                $errores[] = "Error al importar producto: " . $e->getMessage();
            }
        }
    
        if (!empty($errores)) {
            DB::rollBack();
        } else {
            DB::commit();
        }
    
        return $this->handleImportResult($importados, $errores);
    }
    
    private function importJson($file)
    {
        $jsonContent = file_get_contents($file->getPathname());
        $data = json_decode($jsonContent, true);
        $importados = 0;
        $errores = [];
    
        DB::beginTransaction();
    
        foreach ($data as $productData) {
            try {
                $result = $this->createProductFromData($productData);
    
                if ($result['success']) {
                    $importados++;
                } else {
                    $errores[] = $result['error'];
                }
            } catch (\Exception $e) {
                $errores[] = "Error al importar producto: " . $e->getMessage();
            }
        }
    
        if (!empty($errores)) {
            DB::rollBack();
        } else {
            DB::commit();
        }
    
        return $this->handleImportResult($importados, $errores);
    }

    private function createProductFromData($data)
    {
        try {
            // Buscar categoría por nombre
            $categoria = Categoria::where('nombre', $data['categoria'])->first();
            if (!$categoria) {
                return ['success' => false, 'error' => "Categoría no encontrada: " . $data['categoria']];
            }

            // Buscar proveedor por nombre
            $proveedor = Proveedor::where('nombre', $data['proveedor'])->first();
            if (!$proveedor) {
                return ['success' => false, 'error' => "Proveedor no encontrado: " . $data['proveedor']];
            }

            // Crear producto
            Producto::create([
                'categoria_id' => $categoria->categoria_id,
                'proveedor_id' => $proveedor->proveedor_id,
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? '',
                'precio_compra' => $data['precio_compra'],
                'precio_venta' => $data['precio_venta'],
                'stock_actual' => $data['stock_actual'],
                'stock_minimo' => $data['stock_minimo'],
                'estado' => $data['estado'] ?? 'activo',
            ]);

            return ['success' => true];
        } catch (ValidationException $e) {
            return ['success' => false, 'error' => $e->errors()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function handleImportResult($importados, $errores)
    {
        $mensaje = "Se importaron {$importados} productos correctamente.";
        if (!empty($errores)) {
            $mensaje .= " Errores: ";
            $formattedErrors = [];
            foreach ($errores as $error) {
                if (is_array($error)) {
                    foreach ($error as $fieldErrors) {
                        $formattedErrors[] = implode(', ', $fieldErrors);
                    }
                } else {
                    $formattedErrors[] = $error;
                }
            }
            $mensaje .= implode('; ', array_slice($formattedErrors, 0, 5));
            if (count($formattedErrors) > 5) {
                $mensaje .= " y " . (count($formattedErrors) - 5) . " más...";
            }
        }

        return redirect()->route('inventory')->with('status', $mensaje);
    }
}
