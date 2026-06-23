<?php

namespace App\Http\Controllers\Admin\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Support\AdminPerPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->input('name').'%');
        }

        if ($request->filled('contact')) {
            $query->where('primary_contact', 'like', '%'.$request->input('contact').'%');
        }

        // Average is computed before pagination to reflect the full filtered result set
        $averageRating = $query->avg('rating');
        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
        $suppliers = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Suppliers/Index', [
            'suppliers' => $suppliers->getCollection()->map(fn (Supplier $s): array => [
                'supplier_id' => $s->supplier_id,
                'name' => $s->name,
                'primary_contact' => $s->primary_contact,
                'phone' => $s->phone,
                'email' => $s->email,
                'address' => $s->address,
                'delivery_time' => $s->delivery_time,
                'rating' => $s->rating !== null ? (float) $s->rating : null,
                'created_at' => optional($s->created_at)->toIso8601String(),
            ])->values()->all(),
            'averageRating' => (float) ($averageRating ?? 0),
            'pagination' => ListPaginationPayload::from($suppliers),
            'filters' => [
                'name' => (string) $request->input('name', ''),
                'contact' => (string) $request->input('contact', ''),
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('suppliers.index', ['open' => 'new']);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|min:2',
            'primary_contact' => 'required|string|max:100|min:2|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\'\s]+$/',
            'phone' => 'required|string|min:8|max:20',
            'email' => 'required|email|max:100|min:10|unique:suppliers,email',
            'address' => 'required|string|min:5|max:255',
            'delivery_time' => 'required|integer|min:1|max:365',
            'rating' => 'nullable|numeric|min:0|max:5',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede tener más de 100 caracteres.',
            'primary_contact.required' => 'El contacto principal es obligatorio.',
            'primary_contact.min' => 'El contacto principal debe tener al menos 2 caracteres.',
            'primary_contact.regex' => 'El contacto solo puede contener letras y espacios.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.min' => 'El teléfono debe tener al menos 8 dígitos.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ser un correo electrónico válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'email.max' => 'El correo electrónico no puede tener más de 100 caracteres.',
            'email.min' => 'El correo electrónico debe tener al menos 10 caracteres.',
            'address.required' => 'La dirección es obligatoria.',
            'address.min' => 'La dirección debe tener al menos 5 caracteres.',
            'delivery_time.required' => 'El tiempo de entrega es obligatorio.',
            'delivery_time.min' => 'El tiempo de entrega debe ser al menos 1 día.',
            'delivery_time.max' => 'El tiempo de entrega no puede ser mayor a 365 días.',
            'rating.max' => 'La evaluación no puede ser mayor a 5.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación.',
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
                'success' => true,
                'message' => 'Proveedor registrado exitosamente.',
                'redirect' => route('suppliers.index'),
                'data' => $supplier,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar proveedor: '.$e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, string $supplier)
    {
        $supplier = Supplier::find($supplier);
        if (! $supplier) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Proveedor no encontrado.'], 404);
            }

            return redirect()
                ->route('suppliers.index')
                ->with('error', 'Proveedor no encontrado.');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'supplier_id' => $supplier->supplier_id,
                    'name' => $supplier->name,
                    'primary_contact' => $supplier->primary_contact,
                    'email' => $supplier->email,
                    'phone' => $supplier->phone,
                    'address' => $supplier->address,
                    'delivery_time' => $supplier->delivery_time,
                    'rating' => $supplier->rating ?? '0',
                    'status' => 'Activo',
                    'created_at' => $supplier->created_at,
                ],
            ]);
        }

        // Avoid rendering JSON in the browser when navigated directly.
        return redirect()->route('suppliers.index');
    }

    public function edit(string $supplier): RedirectResponse
    {
        // La edición se hace en un modal de la página Inertia; redirigimos al listado.
        return redirect()->route('suppliers.index', ['edit' => $supplier]);
    }

    public function update(Request $request, string $supplier)
    {
        $supplier = Supplier::find($supplier);
        if (! $supplier) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|min:2',
            'primary_contact' => 'required|string|max:100|min:2|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\'\s]+$/',
            'phone' => 'required|string|min:8|max:20',
            // Exclude the current record from the unique check to allow saving without changing the email
            'email' => 'required|email|max:100|min:10|unique:suppliers,email,'.$supplier->supplier_id.',supplier_id',
            'address' => 'required|string|min:5|max:255',
            'delivery_time' => 'required|integer|min:1|max:365',
            'rating' => 'nullable|numeric|min:0|max:5',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede tener más de 100 caracteres.',
            'primary_contact.required' => 'El contacto principal es obligatorio.',
            'primary_contact.min' => 'El contacto principal debe tener al menos 2 caracteres.',
            'primary_contact.regex' => 'El contacto solo puede contener letras y espacios.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.min' => 'El teléfono debe tener al menos 8 dígitos.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ser un correo electrónico válido.',
            'email.max' => 'El correo electrónico no puede tener más de 100 caracteres.',
            'email.min' => 'El correo electrónico debe tener al menos 10 caracteres.',
            'email.unique' => 'Este correo ya está registrado.',
            'address.required' => 'La dirección es obligatoria.',
            'address.min' => 'La dirección debe tener al menos 5 caracteres.',
            'delivery_time.required' => 'El tiempo de entrega es obligatorio.',
            'delivery_time.min' => 'El tiempo de entrega debe ser al menos 1 día.',
            'delivery_time.max' => 'El tiempo de entrega no puede ser mayor a 365 días.',
            'rating.max' => 'La evaluación no puede ser mayor a 5.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación.',
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
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente.',
                'redirect' => route('suppliers.index'),
                'data' => $supplier,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar proveedor: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, string $supplier): RedirectResponse|JsonResponse
    {
        $supplier = Supplier::find($supplier);
        if (! $supplier) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Proveedor no encontrado.'], 404);
            }

            return redirect()
                ->route('suppliers.index')
                ->with('error', 'Proveedor no encontrado.');
        }

        try {
            $supplier->delete();

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Proveedor eliminado exitosamente.']);
            }

            return redirect()
                ->route('suppliers.index')
                ->with('status', 'Proveedor eliminado exitosamente.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                Log::error('Supplier delete failed', [
                    'supplier_id' => $supplier->supplier_id,
                    'exception' => $e,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo completar la acción. Inténtalo nuevamente.',
                ], 500);
            }

            return redirect()
                ->route('suppliers.index')
                ->with('error', 'No se pudo completar la acción. Inténtalo nuevamente.');
        }
    }
}
