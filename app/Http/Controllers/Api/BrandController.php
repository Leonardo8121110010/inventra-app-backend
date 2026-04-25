<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        return response()->json(Brand::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id'    => 'required|string|unique:brands,id',
            'name'  => 'required|string',
        ]);

        $brand = Brand::create($data);
        return response()->json($brand, 201);
    }

    public function update(Request $request, string $id)
    {
        $brand = Brand::findOrFail($id);
        $data = $request->validate([
            'name'  => 'string',
        ]);

        $brand->update($data);
        return response()->json($brand);
    }

    public function destroy(string $id)
    {
        $brand = Brand::findOrFail($id);
        $brand->delete();
        return response()->json(['message' => 'Marca eliminada.']);
    }
}
