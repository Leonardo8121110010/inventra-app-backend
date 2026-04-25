<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashMovement;
use App\Models\CashMovementMotive;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashMovementTest extends TestCase
{
    use RefreshDatabase;

    protected function createAuthorizedUser(array $permissions = [], string $userRole = 'staff')
    {
        $role = Role::create(['name' => $userRole . '-' . uniqid(), 'guard_name' => 'web']);
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $role->syncPermissions($permissions);

        $branch = Branch::create(['id' => uniqid(), 'name' => 'Sucursal Movimientos', 'address' => 'Localidad']);

        $user = User::factory()->create([
            'role' => $role->name, 
            'active' => true,
            'branch_id' => $branch->id
        ]);

        $user->branches()->attach($branch->id);

        return $user;
    }

    public function test_can_list_movements_for_active_register()
    {
        $user = $this->createAuthorizedUser(['manage-cash-registers']);
        Sanctum::actingAs($user);

        $register = CashRegister::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'opening_amount' => 500,
            'status' => 'open'
        ]);

        CashMovement::create([
            'cash_register_id' => $register->id,
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'type' => 'in',
            'amount' => 50,
            'amount_mxn' => 50,
            'currency' => 'MXN',
        ]);

        $response = $this->getJson("/api/cash-movements?branch_id={$user->branch_id}");
        
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $this->assertEquals(50, $response->json('0.amount'));
    }

    public function test_can_store_a_valid_cash_movement()
    {
        $user = $this->createAuthorizedUser(['manage-cash-registers']);
        Sanctum::actingAs($user);

        $register = CashRegister::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'opening_amount' => 1000,
            'status' => 'open'
        ]);

        $motive = CashMovementMotive::create(['id' => uniqid(), 'name' => 'Pago a Proveedor', 'type' => 'out', 'active' => true]);

        $payload = [
            'cash_register_id' => $register->id,
            'branch_id' => $user->branch_id,
            'type' => 'out',
            'amount' => 200,
            'amount_mxn' => 200,
            'currency' => 'MXN',
            'motive_id' => $motive->id,
            'description' => 'Pago de agua'
        ];

        $response = $this->postJson('/api/cash-movements', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('cash_movements', [
            'amount' => 200,
            'type' => 'out',
            'description' => 'Pago de agua'
        ]);
    }

    public function test_cannot_delete_movement_from_closed_register()
    {
        $user = $this->createAuthorizedUser(['manage-cash-registers']);
        Sanctum::actingAs($user);

        $register = CashRegister::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'opening_amount' => 0,
            'status' => 'closed' // Simulated closed register
        ]);

        $movement = CashMovement::create([
            'cash_register_id' => $register->id,
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'type' => 'in',
            'amount' => 100,
            'amount_mxn' => 100,
            'currency' => 'USD',
        ]);

        $response = $this->deleteJson("/api/cash-movements/{$movement->id}");

        $response->assertStatus(422);
        // Db should still have it
        $this->assertDatabaseHas('cash_movements', [
            'id' => $movement->id
        ]);
    }

    public function test_can_get_payout_profile()
    {
        $user = $this->createAuthorizedUser(['view-commissions']);
        Sanctum::actingAs($user);

        $agent = \App\Models\ReferralAgent::create(['id' => uniqid(), 'name' => 'Agent Test', 'status' => 'active']);
        
        $register = CashRegister::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'opening_amount' => 100,
            'status' => 'open'
        ]);

        $sale = \App\Models\Sale::create([
            'id' => uniqid(),
            'branch_id' => $user->branch_id,
            'cash_register_id' => $register->id,
            'user_id' => $user->id,
            'date' => now(),
            'total_amount' => 100,
            'status' => 'completed'
        ]);

        \App\Models\Commission::create([
            'id' => uniqid(),
            'agent_id' => $agent->id,
            'sale_id' => $sale->id,
            'commission_amount' => 50,
            'status' => 'pending',
            'date' => now()
        ]);

        $rule = \App\Models\CommissionRule::create([
            'id' => uniqid(),
            'agent_id' => $agent->id,
            'commission_type' => 'fixed',
            'value' => 200,
            'trigger' => 'visit',
            'period' => 'daily',
            'active' => true
        ]);

        $response = $this->getJson("/api/commissions/payout-profile/{$agent->id}");
        $response->assertStatus(200);
        
        $response->assertJsonCount(1, 'pending_commissions');
        $response->assertJsonCount(1, 'visit_rules');
        $this->assertEquals(50, $response->json('pending_commissions.0.commission_amount'));
        $this->assertEquals(200, $response->json('visit_rules.0.value'));
    }

    public function test_can_store_payout_movement()
    {
        $user = $this->createAuthorizedUser(['manage-cash-registers']);
        Sanctum::actingAs($user);

        $register = CashRegister::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'opening_amount' => 1000,
            'status' => 'open'
        ]);

        $agent = \App\Models\ReferralAgent::create(['id' => uniqid(), 'name' => 'Agent Master', 'status' => 'active']);
        $rule = \App\Models\CommissionRule::create([
            'id' => uniqid(),
            'agent_id' => $agent->id,
            'commission_type' => 'fixed',
            'value' => 150,
            'trigger' => 'visit',
            'period' => 'daily',
            'active' => true
        ]);

        $payload = [
            'cash_register_id' => $register->id,
            'branch_id' => $user->branch_id,
            'type' => 'out',
            'amount' => 150,
            'amount_mxn' => 150,
            'currency' => 'MXN',
            'description' => 'Payout visit',
            'referral_agent_id' => $agent->id,
            'is_payout' => true,
            'visit_rule_id' => $rule->id
        ];

        $response = $this->postJson('/api/cash-movements', $payload);
        $response->assertStatus(201);

        // Verify the movement exists
        $this->assertDatabaseHas('cash_movements', [
            'amount' => 150,
            'type' => 'out',
            'referral_agent_id' => $agent->id
        ]);

        // Verify a new commission was created to log the visit
        $this->assertDatabaseHas('commissions', [
            'agent_id' => $agent->id,
            'rule_id' => $rule->id,
            'status' => 'paid',
            'commission_amount' => 150
        ]);
    }
}
