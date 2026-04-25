<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgentTypeRequest;
use App\Http\Requests\UpdateAgentTypeRequest;
use App\Services\AgentTypeService;
use Illuminate\Http\JsonResponse;

class AgentTypeController extends Controller
{
    protected AgentTypeService $agentTypeService;

    public function __construct(AgentTypeService $agentTypeService)
    {
        $this->agentTypeService = $agentTypeService;
    }

    /**
     * Get all agent types.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->agentTypeService->getAll());
    }

    /**
     * Create a new agent type.
     */
    public function store(StoreAgentTypeRequest $request): JsonResponse
    {
        $type = $this->agentTypeService->create($request->validated());

        return response()->json($type, 201);
    }

    /**
     * Update an existing agent type.
     */
    public function update(UpdateAgentTypeRequest $request, string $id): JsonResponse
    {
        \Log::info('Updating AgentType', ['id' => $id, 'data' => $request->all()]);
        
        $type = \App\Models\AgentType::find($id);

        if (! $type) {
            \Log::warning('AgentType not found', ['id' => $id]);
            return response()->json(['message' => 'Tipo de agente no encontrado'], 404);
        }

        try {
            $updated = $this->agentTypeService->update($type, $request->validated());
            \Log::info('AgentType updated successfully', ['type' => $updated]);
            return response()->json($updated);
        } catch (\Exception $e) {
            \Log::error('Error updating AgentType', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error interno al actualizar'], 500);
        }
    }

    /**
     * Delete an agent type.
     */
    public function destroy(string $id): JsonResponse
    {
        $type = $this->agentTypeService->getAll()->firstWhere('id', $id);

        if (! $type) {
            return response()->json(['message' => 'Tipo de agente no encontrado'], 404);
        }

        try {
            $this->agentTypeService->delete($type);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => 'No se puede eliminar, está en uso'], 400);
        }
    }
}
