<?php

namespace App\Services;

use App\Models\CommissionRule;
use Illuminate\Database\Eloquent\Collection;

class CommissionRuleService
{
    /**
     * Get all commission rules.
     */
    public function getAll(): Collection
    {
        return CommissionRule::all();
    }

    /**
     * Create a new commission rule.
     */
    public function create(array $data): CommissionRule
    {
        // Normalize nullable fields to empty string (legacy convention)
        $data['agent_id']      = $data['agent_id'] ?? '';
        $data['agent_type_id'] = $data['agent_type_id'] ?? '';
        $data['product_id']    = $data['product_id'] ?? '';
        $data['active']        = $data['active'] ?? true;

        return CommissionRule::create($data);
    }

    /**
     * Update an existing commission rule.
     */
    public function update(CommissionRule $rule, array $data): CommissionRule
    {
        // Normalize nullable nulls to empty string
        if (array_key_exists('agent_id', $data) && is_null($data['agent_id'])) {
            $data['agent_id'] = '';
        }
        if (array_key_exists('agent_type_id', $data) && is_null($data['agent_type_id'])) {
            $data['agent_type_id'] = '';
        }
        if (array_key_exists('product_id', $data) && is_null($data['product_id'])) {
            $data['product_id'] = '';
        }

        $rule->update($data);

        return $rule;
    }
}
