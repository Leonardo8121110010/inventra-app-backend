<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'category_id' => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['nullable', 'string', 'exists:suppliers,id'],
            'size_ml'     => ['sometimes', 'integer', 'min:1'],
            'sku'         => ['sometimes', 'string', Rule::unique('products', 'sku')->ignore($id)],
            'barcode'     => ['nullable', 'string', Rule::unique('products', 'barcode')->ignore($id)],
            'cost'        => ['sometimes', 'numeric', 'min:0'],
            'freight'     => ['sometimes', 'numeric', 'min:0'],
            'total_cost'  => ['sometimes', 'numeric', 'min:0'],
            'price'       => ['sometimes', 'numeric', 'min:0'],
            'min_stock'   => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
