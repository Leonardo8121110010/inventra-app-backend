<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommissionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id'               => ['required', 'string', 'unique:commission_rules,id'],
            'agent_id'         => ['nullable', 'string'],
            'agent_type_id'    => ['nullable', 'string'],
            'product_id'       => ['nullable', 'string'],
            'commission_type'  => ['required', 'in:percentage,fixed'],
            'value'            => ['required', 'numeric', 'min:0'],
            'trigger'          => ['required', 'in:visit,sale,volume'],
            'volume_threshold' => ['nullable', 'integer', 'min:0'],
            'period'           => ['nullable', 'in:all_time,monthly,weekly,daily'],
            'active'           => ['boolean'],
        ];
    }
}
