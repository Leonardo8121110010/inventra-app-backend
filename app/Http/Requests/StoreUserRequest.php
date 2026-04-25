<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:6'],
            'role'      => ['required', 'in:admin,gerente,cajero'],
            'branch_id' => ['nullable', 'string', 'exists:branches,id'],
            'avatar'    => ['nullable', 'string', 'max:10'],
        ];
    }
}
