<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReferralAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => ['sometimes', 'string', 'max:255'],
            'types'   => ['sometimes', 'array'],
            'types.*' => ['string', 'exists:agent_types,id'],
            'phone'   => ['nullable', 'sometimes', 'string', 'max:30'],
            'status'  => ['sometimes', 'in:active,inactive'],
        ];
    }
}
