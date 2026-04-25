<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'    => ['sometimes', 'string', 'max:255'],
            'email'   => ['nullable', 'sometimes', 'email', 'max:255'],
            'phone'   => ['nullable', 'sometimes', 'string', 'max:30'],
            'address' => ['nullable', 'sometimes', 'string', 'max:500'],
            'active'  => ['sometimes', 'boolean'],
        ];
    }
}
