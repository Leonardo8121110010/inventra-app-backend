<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReferralAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id'      => ['nullable', 'string', 'unique:referral_agents,id'],
            'name'    => ['required', 'string', 'max:255'],
            'types'   => ['required', 'array', 'min:1'],
            'types.*' => ['string', 'exists:agent_types,id'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'status'  => ['in:active,inactive'],
        ];
    }
}
