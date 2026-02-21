<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Supplier;

class SupplierController extends Controller
{
    // List suppliers with optional search filters and pagination
    public function index()
    {
        $query = Supplier::query();

        if (request('name')) {
            $query->where('name', 'like', '%' . request('name') . '%');
        }

        if (request('contact')) {
            $query->where('primary_contact', 'like', '%' . request('contact') . '%');
        }

        $averageRating = $query->avg('rating');
        $suppliers = $query->paginate(10);

        return view('suppliers.index', compact('suppliers', 'averageRating'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:100|min:2',
            'primary_contact' => 'required|string|max:100|min:2|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\'\s]+$/',
            'phone'           => 'required|string|min:8|max:20',
            // Unique email check scoped to the suppliers table
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

    public function show(string $id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado.'], 404);
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
                'rating'          => $supplier->rating ?? '0', // default to 0 if unrated
                'status'          => 'Activo',
                'created_at'      => $supplier->created_at,
            ]
        ]);
    }

    public function edit(string $id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return redirect()->back()->with('error', 'Proveedor no encontrado.');
        }
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, string $id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:100|min:2',
            'primary_contact' => 'required|string|max:100|min:2|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\'\s]+$/',
            'phone'           => 'required|string|min:8|max:20',
            // Exclude current supplier from unique check using its primary key
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

    public function destroy(string $id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado.'], 404);
        }

        try {
            $supplier->delete();
            return response()->json(['success' => true, 'message' => 'Proveedor eliminado exitosamente.']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar proveedor: ' . $e->getMessage()
            ], 500);
        }
    }
}
