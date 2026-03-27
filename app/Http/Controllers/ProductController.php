<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $perPage = $request->get('per_page', 10);
            $products = Product::with(['category', 'supplier'])
                ->orderBy('product_id', 'desc')
                ->paginate($perPage);
            return response()->json($products);
        }

        return $this->inventory($request);
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $product = DB::transaction(function () use ($request) {
                $data = $request->validated();

                if ($request->hasFile('image')) {
                    $imageName = time().'.'.$request->image->extension();
                    $request->image->move(public_path('assets/images/products'), $imageName);
                    $data['image'] = $imageName;
                }

                if ($request->hasFile('images')) {
                    $paths = [];
                    foreach ($request->file('images') as $i => $file) {
                        $name = time().'_'.$i.'.'.$file->extension();
                        $file->move(public_path('assets/images/products'), $name);
                        $paths[] = $name;
                    }
                    $data['images'] = $paths;
                    // Use the first gallery image as the main thumbnail when none was uploaded
                    if (empty($data['image']) && !empty($paths)) {
                        $data['image'] = $paths[0];
                    }
                }

                return Product::create($data);
            });

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product created successfully',
                    'data' => $product->load(['category', 'supplier'])
                ]);
            }

            return redirect()->route('inventory')->with('status','Product created successfully');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $errors,
                    'message' => 'Please fix the errors in the form',
                ], 422);
            }
            return redirect()->back()->withErrors($errors)->withInput();
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The product could not be created. Please try again.',
                ], 500);
            }
            return redirect()->back()->with('error', 'The product could not be created. Please try again.')->withInput();
        }
    }

    public function show($id)
    {
        try {
            $product = Product::with(['category','supplier'])->findOrFail($id);
            
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $product
                ]);
            }
            
            return view('products.show', compact('product'));
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                    'error' => $e->getMessage()
                ], 404);
            }
            
            return redirect()->route('inventory')->with('error', 'Product not found');
        }
    }

    public function update(UpdateProductRequest $request, $id)
    {
        try {
            $product = DB::transaction(function() use ($request, $id) {
                $p = Product::findOrFail($id);
                $data = $request->validated();

                if ($request->hasFile('image')) {
                    // Delete the old image file before storing the new one
                    if ($p->image && file_exists(public_path('assets/images/products/' . $p->image))) {
                        @unlink(public_path('assets/images/products/' . $p->image));
                    }
                    $imageName = time().'.'.$request->image->extension();
                    $request->image->move(public_path('assets/images/products'), $imageName);
                    $data['image'] = $imageName;
                }

                if ($request->hasFile('images')) {
                    // Remove all existing gallery images before saving the new set
                    $oldImages = $p->images ?? [];
                    foreach (is_array($oldImages) ? $oldImages : [] as $old) {
                        if ($old && file_exists(public_path('assets/images/products/' . $old))) {
                            @unlink(public_path('assets/images/products/' . $old));
                        }
                    }
                    $paths = [];
                    foreach ($request->file('images') as $i => $file) {
                        $name = time().'_'.$i.'.'.$file->extension();
                        $file->move(public_path('assets/images/products'), $name);
                        $paths[] = $name;
                    }
                    $data['images'] = $paths;
                }

                $p->update($data);
                return $p;
            });

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product updated successfully',
                    'data' => $product->load(['category', 'supplier'])
                ]);
            }

            return redirect()->route('inventory')->with('status','Product updated successfully');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $errors,
                    'message' => 'Please fix the errors in the form',
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
                    'message' => 'The product could not be updated. Please try again.',
                ], 500);
            }
            return redirect()->back()->with('error', 'The product could not be updated. Please try again.')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function() use ($id) {
                $p = Product::findOrFail($id);
                // Soft-delete by marking inactive rather than removing the record
                $p->update(['status' => 'inactive']);
            });

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product deactivated successfully',
                ]);
            }
            return redirect()->route('inventory')->with('status','Product deactivated successfully');
        } catch (\Throwable $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deactivating product: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Error deactivating product');
        }
    }

    public function forceDelete($id)
    {
        try {
            DB::transaction(function() use ($id) {
                $p = Product::findOrFail($id);
                $p->delete();
            });

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product permanently deleted',
                ]);
            }
            return redirect()->route('inventory')->with('status','Product permanently deleted');
        } catch (\Throwable $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting product: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'Error deleting product');
        }
    }

    public function create()
    {
        return view('products.create');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        return view('products.edit', compact('product'));
    }

    public function inventory(Request $request)
    {
        $query = Product::with(['category', 'supplier']);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'in-stock':
                    $query->where('stock_current', '>', 10);
                    break;
                case 'low':
                    $query->where('stock_current', '>', 0)->where('stock_current', '<=', 10);
                    break;
                case 'out':
                    $query->where('stock_current', 0);
                    break;
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sort = $request->get('sort', 'product_id');
        $order = $request->get('order', 'desc');
        $query->orderBy($sort, $order);

        $perPage = $request->get('per_page', 10);
        $paginator = $query->paginate($perPage);

        // Normalize raw Eloquent models into a consistent shape expected by the view
        $products = $paginator->getCollection()->map(function($product) {
            return (object)[
                'product_id' => $product->product_id,
                'id' => $product->product_id,
                'name' => $product->name,
                // SKU is derived from the primary key since the table has no dedicated column
                'sku' => 'BK-' . str_pad($product->product_id, 3, '0', STR_PAD_LEFT),
                'image' => $product->image ?? 'default.png',
                'category' => (object)['name' => $product->category?->name ?? 'Uncategorized'],
                'stock' => $product->stock_current,
                'stock_status_class' => $product->stock_current > 10 ? 'success' :
                                      ($product->stock_current > 0 ? 'warning' : 'danger'),
                'price' => $product->sale_price,
                'status' => ucfirst(str_replace('_', ' ', $product->status)),
                'status_class' => $product->status === 'active' ? 'success' :
                                ($product->status === 'inactive' ? 'warning' : 'secondary')
            ];
        });

        $categories = Category::orderBy('name')->get(['category_id', 'name']);

        return view('admin.products.inventory', [
            'products' => $products,
            'paginator' => $paginator,
            'categories' => $categories,
        ]);
    }

    public function export(Request $request, $format = null)
    {
        $format = strtolower($format ?? $request->get('format', 'csv'));
    
        $data = Product::with(['category:category_id,name','supplier:supplier_id,name'])
            ->orderBy('name')
            ->get();
    
        if ($format === 'xml') {
            $xml = new \SimpleXMLElement('<products/>');
            foreach ($data as $p) {
                $n = $xml->addChild('product');
                $n->addChild('id', $p->product_id);
                $n->addChild('name', htmlspecialchars($p->name));
                $n->addChild('description', htmlspecialchars($p->description ?? ''));
                $n->addChild('category', htmlspecialchars(optional($p->category)->name));
                $n->addChild('supplier', htmlspecialchars(optional($p->supplier)->name));
                $n->addChild('purchase_price', number_format((float)$p->purchase_price,2,'.',''));
                $n->addChild('sale_price', number_format((float)$p->sale_price,2,'.',''));
                $n->addChild('stock_current', (string)$p->stock_current);
                $n->addChild('stock_minimum', (string)$p->stock_minimum);
                $n->addChild('status', $p->status);
                $n->addChild('created_at', (string)$p->created_at);
            }
            $filename = 'products_'.date('Ymd_His').'.xml';
            return response($xml->asXML(), 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ]);
        }
    
        if ($format === 'json') {
            $payload = $data->map(function($p){
                return [
                    'id' => $p->product_id,
                    'name' => $p->name,
                    'description' => $p->description,
                    'category' => optional($p->category)->name,
                    'supplier' => optional($p->supplier)->name,
                    'purchase_price' => $p->purchase_price,
                    'sale_price' => $p->sale_price,
                    'stock_current' => $p->stock_current,
                    'stock_minimum' => $p->stock_minimum,
                    'status' => $p->status,
                    'created_at' => $p->created_at,
                ];
            });
            $filename = 'products_'.date('Ymd_His').'.json';
            return response()->streamDownload(function() use ($payload){
                print($payload->toJson(JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            }, $filename, ['Content-Type' => 'application/json; charset=UTF-8']);
        }
        
        if ($format === 'pdf') {
            $filename = 'products_'.date('Ymd_His').'.pdf';
            
            $products = $data->map(function($p) {
                return (object)[
                    'id' => $p->product_id,
                    'name' => $p->name,
                    'description' => $p->description ?? 'No description',
                    'category' => optional($p->category)->name ?? 'Uncategorized',
                    'supplier' => optional($p->supplier)->name ?? 'No supplier',
                    'purchase_price' => number_format((float)$p->purchase_price, 2),
                    'sale_price' => number_format((float)$p->sale_price, 2),
                    'stock_current' => $p->stock_current,
                    'stock_minimum' => $p->stock_minimum,
                    'status' => ucfirst(str_replace('_', ' ', $p->status)),
                    'created_at' => $p->created_at ? $p->created_at->format('d/m/Y') : 'N/A',
                ];
            });
            
            $pdf = PDF::loadView('products.products-pdf', [
                'products' => $products,
                'total' => $products->count(),
                'fecha_exportacion' => now()->format('d/m/Y H:i:s')
            ]);
            
            return $pdf->download($filename);
        }
    
        // Default to CSV export
        $filename = 'products_'.date('Ymd_His').'.csv';
        return response()->streamDownload(function() use ($data){
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for correct Excel rendering
            fputcsv($out, ['ID','Name','Description','Image','Category','Supplier','Purchase Price','Sale Price','Stock','Minimum','Status','Created']);
            foreach ($data as $p) {
                fputcsv($out, [
                    $p->product_id,
                    $p->name,
                    $p->description,
                    $p->image ?? '',
                    optional($p->category)->name,
                    optional($p->supplier)->name,
                    $p->purchase_price,
                    $p->sale_price,
                    $p->stock_current,
                    $p->stock_minimum,
                    $p->status,
                    $p->created_at ? $p->created_at->format('Y-m-d H:i:s') : ''
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

    private function detectFileFormat($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        
        if (in_array($extension, ['xml'])) return 'xml';
        if (in_array($extension, ['csv', 'txt'])) return 'csv';
        if (in_array($extension, ['json'])) return 'json';
        
        // Fall back to content sniffing when the extension is ambiguous
        $content = file_get_contents($file->getPathname());
        $trimmedContent = trim($content);
        
        if (preg_match('/^<\?xml/i', $trimmedContent) || preg_match('/^<[a-zA-Z]/', $trimmedContent)) {
            return 'xml';
        }
        
        if (preg_match('/^[\s]*[\[\{]/', $trimmedContent)) {
            $decoded = json_decode($trimmedContent, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return 'json';
            }
        }
        
        if (in_array($extension, ['txt', 'csv']) || $mimeType === 'text/csv' || $mimeType === 'text/plain') {
            return 'csv';
        }
        
        return null;
    }

    private function importXml($file)
    {
        try {
            $xmlContent = file_get_contents($file->getPathname());
            
            if (empty(trim($xmlContent))) {
                throw new \Exception('El archivo XML está vacío o no se pudo leer correctamente.');
            }
            
            // Capture libxml errors internally instead of emitting PHP warnings
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
        
            if (!isset($xml->producto) || count($xml->producto) == 0) {
                throw new \Exception('No se encontraron productos en el archivo XML.');
            }
        
            foreach ($xml->producto as $productoXml) {
                try {
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
        
            // Roll back the entire import if any record failed to keep the dataset consistent
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
        // First row is treated as the header to build associative arrays per product
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
            $category = Category::where('name', $data['categoria'])->first();
            if (!$category) {
                return ['success' => false, 'error' => "Category not found: " . $data['categoria']];
            }

            $supplier = Supplier::where('name', $data['proveedor'])->first();
            if (!$supplier) {
                return ['success' => false, 'error' => "Supplier not found: " . $data['proveedor']];
            }

            Product::create([
                'category_id' => $category->category_id,
                'supplier_id' => $supplier->supplier_id,
                'name' => $data['nombre'],
                'description' => $data['descripcion'] ?? '',
                'purchase_price' => $data['precio_compra'],
                'sale_price' => $data['precio_venta'],
                'stock_current' => $data['stock_actual'],
                'stock_minimum' => $data['stock_minimo'],
                // Translate Spanish status values from imported files to internal English enums
                'status' => $this->mapLegacyStatus($data['estado'] ?? 'activo'),
            ]);

            return ['success' => true];
        } catch (ValidationException $e) {
            return ['success' => false, 'error' => $e->errors()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function mapLegacyStatus(string $status): string
    {
        // Maps Spanish status labels used in import files to the internal English enum values
        return match (strtolower($status)) {
            'activo' => 'active',
            'inactivo' => 'inactive',
            'agotado' => 'out_of_stock',
            'descontinuado' => 'discontinued',
            default => $status,
        };
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
            // Cap the displayed error list at 5 to keep the flash message readable
            $mensaje .= implode('; ', array_slice($formattedErrors, 0, 5));
            if (count($formattedErrors) > 5) {
                $mensaje .= " y " . (count($formattedErrors) - 5) . " más...";
            }
        }

        return redirect()->route('inventory')->with('status', $mensaje);
    }
}