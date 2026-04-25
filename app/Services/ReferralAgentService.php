<?php

namespace App\Services;

use App\Models\ReferralAgent;
use Illuminate\Database\Eloquent\Collection;

class ReferralAgentService
{
    /**
     * Get all referral agents with their types.
     */
    public function getAll(): Collection
    {
        return ReferralAgent::with('agentTypes')->get();
    }

    /**
     * Create a new referral agent.
     */
    public function create(array $data): ReferralAgent
    {
        $types = $data['types'] ?? [];
        unset($data['types']);

        // Auto-generate ID if not provided
        if (empty($data['id'])) {
            $data['id'] = 'ra_' . bin2hex(random_bytes(6));
        }

        $data['status'] = $data['status'] ?? 'active';

        $agent = ReferralAgent::create($data);
        $agent->agentTypes()->sync($types);

        return $agent->load('agentTypes');
    }

    /**
     * Update an existing referral agent.
     */
    public function update(ReferralAgent $agent, array $data): ReferralAgent
    {
        if (isset($data['types'])) {
            $types = $data['types'];
            unset($data['types']);
            $agent->agentTypes()->sync($types);
        }

        $agent->update($data);

        return $agent->load('agentTypes');
    }
}
