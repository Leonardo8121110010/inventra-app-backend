<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id'          => ['required', 'string', 'unique:products,id'],
            'name'        => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['nullable', 'string', 'exists:suppliers,id'],
            'size_ml'     => ['nullable', 'integer', 'min:1'],
            'sku'         => ['required', 'string', 'unique:products,sku'],
            'barcode'     => ['nullable', 'string', 'unique:products,barcode'],
            'cost'        => ['numeric', 'min:0'],
            'freight'     => ['numeric', 'min:0'],
            'total_cost'  => ['numeric', 'min:0'],
            'price'       => ['numeric', 'min:0'],
            'min_stock'   => ['integer', 'min:0'],
        ];
    }
}
