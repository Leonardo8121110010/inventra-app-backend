<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    /**
     * Get all available permissions.
     */
    public function index(): JsonResponse
    {
        return response()->json(Permission::orderBy('display_name')->get());
    }
}
