<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Get all users.
     */
    public function getAll(): Collection
    {
        return User::select('id', 'name', 'email', 'role', 'branch_id', 'avatar')->get();
    }

    /**
     * Create a new user.
     */
    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        return User::create($data);
    }

    /**
     * Update an existing user.
     */
    public function update(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return $user;
    }

    /**
     * Delete a user.
     */
    public function delete(User $user): void
    {
        $user->delete();
    }
}
