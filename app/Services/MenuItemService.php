<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Collection;

class MenuItemService
{
    /**
     * Get all menu items ordered by sort_order.
     */
    public function getAll(): Collection
    {
        return MenuItem::orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    /**
     * Get active menu items (for sidebar consumption).
     */
    public function getActive(): Collection
    {
        return MenuItem::where('active', true)
            ->orderBy('section')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Create a new menu item.
     */
    public function create(array $data): MenuItem
    {
        return MenuItem::create($data);
    }

    /**
     * Update an existing menu item.
     */
    public function update(MenuItem $item, array $data): MenuItem
    {
        $item->update($data);

        return $item;
    }

    /**
     * Delete a menu item (and its children if any).
     */
    public function delete(MenuItem $item): void
    {
        $item->children()->delete();
        $item->delete();
    }
}
