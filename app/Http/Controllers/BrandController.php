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
            'name' => 'required|string|max:100|unique:brands,name',
        ], [
            'name.required' => 'El nombre de la marca es obligatorio.',
            'name.unique'   => 'Esta marca ya está registrada.',
            'name.max'      => 'El nombre no puede tener más de 100 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
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
            'name' => 'required|string|max:100|unique:brands,name,' . $brand->id,
        ], [
            'name.required' => 'El nombre de la marca es obligatorio.',
            'name.unique'   => 'Esta marca ya está registrada.',
            'name.max'      => 'El nombre no puede tener más de 100 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
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
        $brand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Marca eliminada correctamente.',
        ]);
    }
}
