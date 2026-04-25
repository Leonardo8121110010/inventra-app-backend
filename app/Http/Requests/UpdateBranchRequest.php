<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => ['sometimes', 'required', 'string', 'max:255'],
            'type'    => ['nullable', 'string', 'in:matriz,sucursal'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
