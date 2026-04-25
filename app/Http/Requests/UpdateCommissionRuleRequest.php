<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommissionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_id'         => ['sometimes', 'nullable', 'string'],
            'agent_type_id'    => ['sometimes', 'nullable', 'string'],
            'product_id'       => ['sometimes', 'nullable', 'string'],
            'commission_type'  => ['sometimes', 'in:percentage,fixed'],
            'value'            => ['sometimes', 'numeric', 'min:0'],
            'trigger'          => ['sometimes', 'in:visit,sale,volume'],
            'volume_threshold' => ['nullable', 'integer', 'min:0'],
            'period'           => ['nullable', 'in:all_time,monthly,weekly,daily'],
            'active'           => ['sometimes', 'boolean'],
        ];
    }
}
