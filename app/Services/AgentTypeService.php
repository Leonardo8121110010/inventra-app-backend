<?php

namespace App\Services;

use App\Models\AgentType;
use Illuminate\Database\Eloquent\Collection;

class AgentTypeService
{
    /**
     * Get all agent types.
     */
    public function getAll(): Collection
    {
        return AgentType::all();
    }

    /**
     * Create a new agent type.
     */
    public function create(array $data): AgentType
    {
        return AgentType::create($data);
    }

    /**
     * Update an existing agent type.
     */
    public function update(AgentType $type, array $data): AgentType
    {
        $type->update($data);

        return $type;
    }

    /**
     * Delete an agent type (Deactivates it instead).
     */
    public function delete(AgentType $type): void
    {
        $type->update(['status' => 'inactive']);
    }
}
