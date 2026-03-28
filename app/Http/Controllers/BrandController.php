<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    public function index()
    {
        $query = Brand::query();

        if (request('name')) {
            $query->where('name', 'like', '%' . request('name') . '%');
        }

        $brands = $query->orderBy('name')->paginate(15);

        return view('admin.brands.index', compact('brands'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ], [
            'name.required' => 'El nombre de la marca es obligatorio.',
            'name.max'      => 'El nombre no puede tener más de 100 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Exact case-sensitive match
        $exactMatch = Brand::whereRaw('BINARY name = ?', [$request->name])->first();
        if ($exactMatch) {
            return response()->json([
                'success'   => false,
                'duplicate' => true,
                'exact'     => true,
                'existing'  => ['id' => $exactMatch->id, 'name' => $exactMatch->name],
            ], 422);
        }

        // Case-insensitive match (different capitalization)
        $existing = Brand::whereRaw('LOWER(name) = ?', [strtolower($request->name)])->first();
        if ($existing) {
            return response()->json([
                'success'   => false,
                'duplicate' => true,
                'exact'     => false,
                'existing'  => ['id' => $existing->id, 'name' => $existing->name],
            ], 422);
        }

        $brand = Brand::create(['name' => $request->name]);

        return response()->json([
            'success' => true,
            'message' => 'Marca creada correctamente.',
            'brand'   => $brand,
        ]);
    }

    public function update(Request $request, Brand $brand)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ], [
            'name.required' => 'El nombre de la marca es obligatorio.',
            'name.max'      => 'El nombre no puede tener más de 100 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Exact case-sensitive match (excluding self)
        $exactMatch = Brand::whereRaw('BINARY name = ?', [$request->name])
            ->where('id', '!=', $brand->id)
            ->first();
        if ($exactMatch) {
            return response()->json([
                'success'   => false,
                'duplicate' => true,
                'exact'     => true,
                'existing'  => ['id' => $exactMatch->id, 'name' => $exactMatch->name],
            ], 422);
        }

        // Case-insensitive match excluding self (different capitalization)
        $existing = Brand::whereRaw('LOWER(name) = ?', [strtolower($request->name)])
            ->where('id', '!=', $brand->id)
            ->first();
        if ($existing) {
            return response()->json([
                'success'   => false,
                'duplicate' => true,
                'exact'     => false,
                'existing'  => ['id' => $existing->id, 'name' => $existing->name],
            ], 422);
        }

        $brand->update(['name' => $request->name]);

        return response()->json([
            'success' => true,
            'message' => 'Marca actualizada correctamente.',
            'brand'   => $brand,
        ]);
    }

    public function destroy(Brand $brand)
    {
        try {
            $productCount = $brand->products()->count();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar los productos asociados: ' . $e->getMessage(),
            ], 500);
        }

        if ($productCount > 0) {
            return response()->json([
                'success' => false,
                'blocked' => true,
                'message' => "No se puede eliminar \"{$brand->name}\" porque está asociada a {$productCount} " . ($productCount === 1 ? 'producto' : 'productos') . '.',
            ], 422);
        }

        $brand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Marca eliminada correctamente.',
        ]);
    }
}
