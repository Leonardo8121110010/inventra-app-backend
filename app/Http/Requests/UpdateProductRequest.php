<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['sometimes', 'string', 'max:255'],
            'icon'      => ['nullable', 'string', 'max:50'],
            'color'     => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'string', 'exists:categories,id'],
        ];
    }
}
