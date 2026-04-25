<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name'      => ['sometimes', 'string', 'max:255'],
            'email'     => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($id)],
            'password'  => ['nullable', 'string', 'min:6'],
            'role'      => ['sometimes', 'in:admin,gerente,cajero'],
            'branch_id' => ['nullable', 'string', 'exists:branches,id'],
            'avatar'    => ['nullable', 'string', 'max:10'],
        ];
    }
}
