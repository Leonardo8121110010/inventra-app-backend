<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(Category::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id'        => 'required|string|unique:categories,id',
            'name'      => 'required|string',
            'icon'      => 'nullable|string',
            'color'     => 'nullable|string',
            'parent_id' => 'nullable|string|exists:categories,id',
        ]);

        $category = Category::create($data);
        return response()->json($category, 201);
    }

    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);
        $data = $request->validate([
            'name'      => 'string',
            'icon'      => 'nullable|string',
            'color'     => 'nullable|string',
            'parent_id' => 'nullable|string|exists:categories,id',
        ]);

        $category->update($data);
        return response()->json($category);
    }

    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        // We could restrict deletion if products exist, but for now just delete
        $category->delete();
        return response()->json(['message' => 'Categoría eliminada.']);
    }
}
