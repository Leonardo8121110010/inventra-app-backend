<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Services\MenuItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    protected MenuItemService $menuItemService;

    public function __construct(MenuItemService $menuItemService)
    {
        $this->menuItemService = $menuItemService;
    }

    /**
     * Get all menu items.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->menuItemService->getAll());
    }

    /**
     * Create a new menu item.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'parent_id'  => 'nullable|exists:menu_items,id',
            'label'      => 'required|string|max:255',
            'icon'       => 'nullable|string|max:50',
            'route_key'  => 'required|string|max:100|unique:menu_items,route_key',
            'section'    => 'required|string|max:50',
            'sort_order' => 'integer|min:0',
            'active'     => 'boolean',
        ]);

        $data['active'] = $data['active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $item = $this->menuItemService->create($data);

        return response()->json($item, 201);
    }

    /**
     * Update an existing menu item.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = MenuItem::findOrFail($id);

        $data = $request->validate([
            'parent_id'  => 'nullable|exists:menu_items,id',
            'label'      => 'sometimes|string|max:255',
            'icon'       => 'nullable|string|max:50',
            'route_key'  => 'sometimes|string|max:100|unique:menu_items,route_key,' . $id,
            'section'    => 'sometimes|string|max:50',
            'sort_order' => 'integer|min:0',
            'active'     => 'sometimes|boolean',
        ]);

        $updated = $this->menuItemService->update($item, $data);

        return response()->json($updated);
    }

    /**
     * Delete a menu item.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = MenuItem::findOrFail($id);
        $this->menuItemService->delete($item);

        return response()->json(['message' => 'Menú eliminado correctamente']);
    }
}
