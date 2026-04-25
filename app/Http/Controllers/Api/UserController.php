<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Get all users with their branch assignments.
     */
    public function index(): JsonResponse
    {
        $users = User::select('id', 'name', 'email', 'role', 'branch_id', 'avatar', 'active')
            ->with('branches:id,name')
            ->get()
            ->map(function ($user) {
                $branches = $user->branches->map(fn($b) => [
                    'id'         => $b->id,
                    'name'       => $b->name,
                    'is_primary' => $b->pivot->is_primary ?? false,
                ]);

                // Fallback: if no pivot records exist (migrated users), use branch_id
                if ($branches->isEmpty() && $user->branch_id) {
                    $branches = collect([[
                        'id'         => $user->branch_id,
                        'name'       => $user->branch_id,
                        'is_primary' => true,
                    ]]);
                }

                return [
                    'id'        => $user->id,
                    'name'      => $user->name,
                    'email'     => $user->email,
                    'role'      => $user->role,
                    'branch_id' => $user->branch_id,
                    'avatar'    => $user->avatar,
                    'active'    => $user->active,
                    'branches'  => $branches,
                ];
            });

        return response()->json($users);
    }

    /**
     * Create a new user with optional branch assignments.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:6',
            'role'      => 'required|in:admin,gerente,cajero',
            'branch_id' => 'nullable|string|exists:branches,id',
            'branches'  => 'sometimes|array',
            'branches.*'=> 'string|exists:branches,id',
            'avatar'    => 'nullable|string|max:10',
            'active'    => 'boolean',
        ]);

        $data['password'] = Hash::make($data['password']);
        $branches = $data['branches'] ?? [];
        unset($data['branches']);

        // If branch_id provided but no branches array, use branch_id as single branch
        if (empty($branches) && !empty($data['branch_id'])) {
            $branches = [$data['branch_id']];
        }

        $user = User::create($data);

        if (!empty($branches)) {
            $syncData = [];
            foreach ($branches as $branchId) {
                $syncData[$branchId] = ['is_primary' => $branchId === ($data['branch_id'] ?? null)];
            }
            $user->branches()->sync($syncData);
        }

        return response()->json($this->formatUserWithBranches($user), 201);
    }

    /**
     * Update an existing user with optional branch reassignment.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name'      => 'string',
            'email'     => 'email|unique:users,email,' . $id,
            'password'  => 'nullable|string|min:6',
            'role'      => 'in:admin,gerente,cajero',
            'branch_id' => 'nullable|string|exists:branches,id',
            'branches'  => 'sometimes|array',
            'branches.*'=> 'string|exists:branches,id',
            'avatar'    => 'nullable|string|max:10',
            'active'    => 'boolean',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        // Sync branches if provided
        if (isset($data['branches'])) {
            $branches = $data['branches'];
            $syncData = [];
            foreach ($branches as $branchId) {
                $syncData[$branchId] = ['is_primary' => $branchId === ($data['branch_id'] ?? $user->branch_id)];
            }
            $user->branches()->sync($syncData);
        }

        return response()->json($this->formatUserWithBranches($user));
    }

    /**
     * Delete a user.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['active' => false]);

        return response()->json(['message' => 'Usuario desactivado correctamente']);
    }

    /**
     * Format user with branches array for frontend consumption.
     */
    protected function formatUserWithBranches(User $user): array
    {
        $user->load('branches:id,name');

        $branches = $user->branches->map(fn($b) => [
            'id'         => $b->id,
            'name'       => $b->name,
            'is_primary' => $b->pivot->is_primary ?? false,
        ]);

        return [
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'role'      => $user->role,
            'branch_id' => $user->branch_id,
            'avatar'    => $user->avatar,
            'active'    => $user->active,
            'branches'  => $branches,
        ];
    }
}
