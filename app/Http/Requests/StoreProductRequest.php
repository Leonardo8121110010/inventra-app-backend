<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id'        => ['required', 'string', 'unique:categories,id'],
            'name'      => ['required', 'string', 'max:255'],
            'icon'      => ['nullable', 'string', 'max:50'],
            'color'     => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'string', 'exists:categories,id'],
        ];
    }
}
