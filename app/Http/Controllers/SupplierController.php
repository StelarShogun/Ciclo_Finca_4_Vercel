<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Supplier;

class SupplierController extends Controller
{
    // Listar proveedores con búsqueda y paginación
    public function index()
    {
        $query = Supplier::query();

        // Aplicar filtros
        if (request('nombre')) {
            $nombre = request('nombre');
            $query->where('nombre', 'like', "%{$nombre}%");
        }

        if (request('contacto')) {
            $contacto = request('contacto');
            $query->where('contacto_principal', 'like', "%{$contacto}%");
        }

        $promedioEvaluacion = $query->avg('evaluacion');
        $proveedores = $query->paginate(10);

        return view('proveedores.index', compact('proveedores', 'promedioEvaluacion'));
    }

    // Mostrar formulario de creación
    public function create()
    {
        return view('proveedores.create');
    }

    // Guardar nuevo proveedor
    public function store(Request $request)
    {
        // #region agent log
        $logPath = base_path('.cursor/debug.log');
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logData = [
            'location' => 'ProveedorController.php:38',
            'message' => 'Store method called - CSRF check',
            'data' => [
                'hasCsrfToken' => $request->has('_token'),
                'hasXCsrfHeader' => $request->hasHeader('X-CSRF-TOKEN'),
                'method' => $request->method(),
                'isAjax' => $request->ajax(),
                'wantsJson' => $request->wantsJson(),
            ],
            'timestamp' => time() * 1000,
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'B'
        ];
        @file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        // #endregion
        
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|min:2',
            'contacto_principal' => 'required|string|max:100|min:2|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\'\s]+$/',
            'telefono' => 'required|string|min:8|max:20',
            'correo_electronico' => 'required|email|max:100|min:10|unique:proveedores,correo_electronico',
            'direccion' => 'required|string|min:5|max:255',
            'tiempo_entrega' => 'required|integer|min:1|max:365',
            'evaluacion' => 'nullable|numeric|min:0|max:5',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no puede tener más de 100 caracteres.',
            'contacto_principal.required' => 'El contacto principal es obligatorio.',
            'contacto_principal.min' => 'El contacto principal debe tener al menos 2 caracteres.',
            'contacto_principal.regex' => 'El contacto solo puede contener letras y espacios.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.min' => 'El teléfono debe tener al menos 8 dígitos.',
            'correo_electronico.required' => 'El correo electrónico es obligatorio.',
            'correo_electronico.email' => 'Debe ser un correo electrónico válido.',
            'correo_electronico.unique' => 'Este correo ya está registrado.',
            'correo_electronico.max' => 'El correo electrónico no puede tener más de 100 caracteres.',
            'correo_electronico.min' => 'El correo electrónico debe tener al menos 10 caracteres.',
            'direccion.required' => 'La dirección es obligatoria.',
            'direccion.min' => 'La dirección debe tener al menos 5 caracteres.',
            'tiempo_entrega.required' => 'El tiempo de entrega es obligatorio.',
            'tiempo_entrega.min' => 'El tiempo de entrega debe ser al menos 1 día.',
            'tiempo_entrega.max' => 'El tiempo de entrega no puede ser mayor a 365 días.',
            'evaluacion.max' => 'La evaluación no puede ser mayor a 5.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación.'
            ], 422);
        }

        try {
            $Supplier = Supplier::create($request->only(
                'nombre',
                'contacto_principal',
                'telefono',
                'correo_electronico',
                'direccion',
                'tiempo_entrega',
                'evaluacion'
            ));

            return response()->json([
                'success' => true,
                'message' => 'Proveedor registrado exitosamente.',
                'redirect' => route('proveedores.index'),
                'data' => $Supplier
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar proveedor: ' . $e->getMessage()
            ], 500);
        }
    }

    // Mostrar un proveedor
    public function show(string $id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado.'
            ], 404);
        }
        
        // Mapear los campos al formato esperado por el frontend
        return response()->json([
            'success' => true,
            'data' => [
                'nombre' => $supplier->nombre,
                'email' => $supplier->correo_electronico,
                'telefono' => $supplier->telefono,
                'direccion' => $supplier->direccion,
                'evaluacion' => $supplier->evaluacion ?? '0',
                'estado' => 'Activo', // Campo calculado si es necesario
                'created_at' => $supplier->fecha_creacion,
            ]
        ]);
    }

    // Mostrar formulario de edición
    public function edit(string $id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return redirect()->back()->with('error', 'Proveedor no encontrado.');
        }
        return view('proveedores.edit', compact('proveedor'));
    }

    // Actualizar proveedor
    public function update(Request $request, string $id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|min:2',
            'contacto_principal' => 'required|string|max:100|min:2|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\'\s]+$/',
            'telefono' => 'required|string|min:8|max:20',
            'correo_electronico' => 'required|email|max:100|min:10|unique:proveedores,correo_electronico,' . $supplier->supplier_id . ',proveedor_id',
            'direccion' => 'required|string|min:5|max:255',
            'tiempo_entrega' => 'required|integer|min:1|max:365',
            'evaluacion' => 'nullable|numeric|min:0|max:5',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no puede tener más de 100 caracteres.',
            'contacto_principal.required' => 'El contacto principal es obligatorio.',
            'contacto_principal.min' => 'El contacto principal debe tener al menos 2 caracteres.',
            'contacto_principal.regex' => 'El contacto solo puede contener letras y espacios.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.min' => 'El teléfono debe tener al menos 8 dígitos.',
            'correo_electronico.required' => 'El correo electrónico es obligatorio.',
            'correo_electronico.email' => 'Debe ser un correo electrónico válido.',
            'correo_electronico.max' => 'El correo electrónico no puede tener más de 100 caracteres.',
            'email.min' => 'El correo electrónico debe tener al menos 10 caracteres.',
            'email.unique' => 'Este correo ya está registrado.',
            'direccion.required' => 'La dirección es obligatoria.',
            'direccion.min' => 'La dirección debe tener al menos 5 caracteres.',
            'tiempo_entrega.required' => 'El tiempo de entrega es obligatorio.',
            'tiempo_entrega.min' => 'El tiempo de entrega debe ser al menos 1 día.',
            'tiempo_entrega.max' => 'El tiempo de entrega no puede ser mayor a 365 días.',
            'evaluacion.max' => 'La evaluación no puede ser mayor a 5.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación.'
            ], 422);
        }

        try {
            $supplier->update($request->only(
                'nombre',
                'contacto_principal',
                'telefono',
                'correo_electronico',
                'direccion',
                'tiempo_entrega',
                'evaluacion'
            ));

            return response()->json([
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente.',
                'redirect' => route('proveedores.index'),
                'data' => $supplier
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar proveedor: ' . $e->getMessage()
            ], 500);
        }
    }

    // Eliminar proveedor
    public function destroy(string $id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado.'
            ], 404);
        }

        try {
            $supplier->delete();
            return response()->json([
                'success' => true,
                'message' => 'Proveedor eliminado exitosamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar proveedor: ' . $e->getMessage()
            ], 500);
        }
    }
}
