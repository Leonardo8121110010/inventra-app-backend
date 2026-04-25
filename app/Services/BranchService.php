<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;

class BranchService
{
    /**
     * Get all branches.
     */
    public function getAll(): Collection
    {
        return Branch::all();
    }

    /**
     * Create a new branch.
     */
    public function create(array $data): Branch
    {
        $data['type'] = $data['type'] ?? 'sucursal';

        return Branch::create($data);
    }

    /**
     * Update an existing branch.
     */
    public function update(Branch $branch, array $data): Branch
    {
        $branch->update($data);

        return $branch;
    }

    /**
     * Delete a branch.
     *
     * @throws \Exception if the branch has related data
     */
    public function delete(Branch $branch): void
    {
        $branch->delete();
    }
}
