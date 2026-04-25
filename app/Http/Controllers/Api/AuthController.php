<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas.'],
            ]);
        }

        $token = $user->createToken('pos-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->formatUser($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json($this->formatUser($user));
    }

    protected function formatUser(User $user): array
    {
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
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->role,
            'branch_id'   => $user->branch_id,
            'avatar'      => $user->avatar,
            'branches'    => $branches,
            'permissions' => method_exists($user, 'getPermissions') ? $user->getPermissions() : [],
        ];
    }
}
