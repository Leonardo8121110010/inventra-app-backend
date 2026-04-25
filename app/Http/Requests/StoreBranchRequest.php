<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id'      => ['required', 'string', 'unique:branches,id'],
            'name'    => ['required', 'string', 'max:255'],
            'type'    => ['nullable', 'string', 'in:matriz,sucursal'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
