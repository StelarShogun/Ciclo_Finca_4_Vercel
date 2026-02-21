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
        if (request('name')) {
            $name = request('name');
            $query->where('name', 'like', "%{$name}%");
        }

        if (request('contact')) {
            $contact = request('contact');
            $query->where('primary_contact', 'like', "%{$contact}%");
        }

        $averageRating = $query->avg('rating');
        $suppliers = $query->paginate(10);

        return view('suppliers.index', compact('suppliers', 'averageRating'));
    }

    // Mostrar formulario de creación
    public function create()
    {
        return view('suppliers.create');
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
            'location' => 'SupplierController.php:38',
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
            'name'            => 'required|string|max:100|min:2',
            'primary_contact' => 'required|string|max:100|min:2|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\'\s]+$/',
            'phone'           => 'required|string|min:8|max:20',
            'email'           => 'required|email|max:100|min:10|unique:suppliers,email',
            'address'         => 'required|string|min:5|max:255',
            'delivery_time'   => 'required|integer|min:1|max:365',
            'rating'          => 'nullable|numeric|min:0|max:5',
        ], [
            'name.required'            => 'El nombre es obligatorio.',
            'name.min'                 => 'El nombre debe tener al menos 2 caracteres.',
            'name.max'                 => 'El nombre no puede tener más de 100 caracteres.',
            'primary_contact.required' => 'El contacto principal es obligatorio.',
            'primary_contact.min'      => 'El contacto principal debe tener al menos 2 caracteres.',
            'primary_contact.regex'    => 'El contacto solo puede contener letras y espacios.',
            'phone.required'           => 'El teléfono es obligatorio.',
            'phone.min'                => 'El teléfono debe tener al menos 8 dígitos.',
            'email.required'           => 'El correo electrónico es obligatorio.',
            'email.email'              => 'Debe ser un correo electrónico válido.',
            'email.unique'             => 'Este correo ya está registrado.',
            'email.max'                => 'El correo electrónico no puede tener más de 100 caracteres.',
            'email.min'                => 'El correo electrónico debe tener al menos 10 caracteres.',
            'address.required'         => 'La dirección es obligatoria.',
            'address.min'              => 'La dirección debe tener al menos 5 caracteres.',
            'delivery_time.required'   => 'El tiempo de entrega es obligatorio.',
            'delivery_time.min'        => 'El tiempo de entrega debe ser al menos 1 día.',
            'delivery_time.max'        => 'El tiempo de entrega no puede ser mayor a 365 días.',
            'rating.max'               => 'La evaluación no puede ser mayor a 5.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
                'message' => 'Error de validación.'
            ], 422);
        }

        try {
            $supplier = Supplier::create($request->only(
                'name',
                'primary_contact',
                'phone',
                'email',
                'address',
                'delivery_time',
                'rating'
            ));

            return response()->json([
                'success'  => true,
                'message'  => 'Proveedor registrado exitosamente.',
                'redirect' => route('suppliers.index'),
                'data'     => $supplier
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

        return response()->json([
            'success' => true,
            'data' => [
                'supplier_id'     => $supplier->supplier_id,  
                'name'            => $supplier->name,
                'primary_contact' => $supplier->primary_contact,  
                'email'           => $supplier->email,
                'phone'           => $supplier->phone,
                'address'         => $supplier->address,
                'delivery_time'   => $supplier->delivery_time,  
                'rating'          => $supplier->rating ?? '0',
                'status'          => 'Activo',
                'created_at'      => $supplier->created_at,
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
        return view('suppliers.edit', compact('supplier'));
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
            'name'            => 'required|string|max:100|min:2',
            'primary_contact' => 'required|string|max:100|min:2|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\'\s]+$/',
            'phone'           => 'required|string|min:8|max:20',
            'email'           => 'required|email|max:100|min:10|unique:suppliers,email,' . $supplier->supplier_id . ',supplier_id',
            'address'         => 'required|string|min:5|max:255',
            'delivery_time'   => 'required|integer|min:1|max:365',
            'rating'          => 'nullable|numeric|min:0|max:5',
        ], [
            'name.required'            => 'El nombre es obligatorio.',
            'name.min'                 => 'El nombre debe tener al menos 2 caracteres.',
            'name.max'                 => 'El nombre no puede tener más de 100 caracteres.',
            'primary_contact.required' => 'El contacto principal es obligatorio.',
            'primary_contact.min'      => 'El contacto principal debe tener al menos 2 caracteres.',
            'primary_contact.regex'    => 'El contacto solo puede contener letras y espacios.',
            'phone.required'           => 'El teléfono es obligatorio.',
            'phone.min'                => 'El teléfono debe tener al menos 8 dígitos.',
            'email.required'           => 'El correo electrónico es obligatorio.',
            'email.email'              => 'Debe ser un correo electrónico válido.',
            'email.max'                => 'El correo electrónico no puede tener más de 100 caracteres.',
            'email.min'                => 'El correo electrónico debe tener al menos 10 caracteres.',
            'email.unique'             => 'Este correo ya está registrado.',
            'address.required'         => 'La dirección es obligatoria.',
            'address.min'              => 'La dirección debe tener al menos 5 caracteres.',
            'delivery_time.required'   => 'El tiempo de entrega es obligatorio.',
            'delivery_time.min'        => 'El tiempo de entrega debe ser al menos 1 día.',
            'delivery_time.max'        => 'El tiempo de entrega no puede ser mayor a 365 días.',
            'rating.max'               => 'La evaluación no puede ser mayor a 5.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
                'message' => 'Error de validación.'
            ], 422);
        }

        try {
            $supplier->update($request->only(
                'name',
                'primary_contact',
                'phone',
                'email',
                'address',
                'delivery_time',
                'rating'
            ));

            return response()->json([
                'success'  => true,
                'message'  => 'Proveedor actualizado exitosamente.',
                'redirect' => route('suppliers.index'),
                'data'     => $supplier
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
