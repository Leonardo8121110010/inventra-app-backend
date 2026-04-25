<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithRole(string $roleName, array $permissions = [], string $branchId = 'br-1')
    {
        $role = Role::create(['name' => $roleName, 'guard_name' => 'web']);
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $role->syncPermissions($permissions);

        $branch = Branch::create(['id' => $branchId, 'name' => 'Branch ' . $branchId]);
        
        $user = User::factory()->create([
            'role' => $role->name,
            'branch_id' => $branch->id,
            'active' => true
        ]);

        $user->branches()->attach($branch->id);

        return compact('user', 'branch');
    }

    public function test_cajero_cannot_access_administrative_reports()
    {
        // Setup a non-admin user (Cajero)
        $data = $this->setupUserWithRole('cajero', ['create-sales']);
        Sanctum::actingAs($data['user']);

        // Try to access inventory report (requires view-inventory)
        $response = $this->getJson('/api/reports/daily-inventory?branch_id=' . $data['branch']->id);

        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'No tiene el permiso requerido: Ver Inventario']);
    }

    public function test_branch_isolation_prevent_viewing_other_branch_sale()
    {
        // 1. User in Branch A
        $dataA = $this->setupUserWithRole('staff-a', ['view-sales'], 'branch-a');
        
        // 2. Sale in Branch B
        $branchB = Branch::create(['id' => 'branch-b', 'name' => 'Branch B']);
        $saleB = Sale::create([
            'id' => 'sale-b-001',
            'branch_id' => $branchB->id,
            'user_id' => uniqid(),
            'total' => 100,
            'total_mxn' => 100,
            'currency' => 'MXN',
            'date' => now()
        ]);

        Sanctum::actingAs($dataA['user']);

        // 3. Try to access Sale B from User A context
        $response = $this->getJson("/api/sales/{$saleB->id}");

        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'No tienes acceso a la sucursal de esta venta']);
    }

    public function test_admin_can_bypass_branch_isolation()
    {
        // Admin user
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        
        // Sale in a random branch
        $branchRandom = Branch::create(['id' => 'br-random', 'name' => 'Random']);
        $sale = Sale::create([
            'id' => 'sale-x',
            'branch_id' => $branchRandom->id,
            'user_id' => uniqid(),
            'total' => 50,
            'total_mxn' => 50,
            'currency' => 'MXN',
            'date' => now()
        ]);

        Sanctum::actingAs($admin);

        // Admin should be able to see it
        $this->getJson("/api/sales/{$sale->id}")->assertStatus(200);
    }
}
