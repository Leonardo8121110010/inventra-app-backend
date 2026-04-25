<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\CashMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function createAuthorizedUser(array $permissions = [], string $userRole = 'staff')
    {
        $role = Role::create(['name' => $userRole . '-' . uniqid(), 'guard_name' => 'web']);
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $role->syncPermissions($permissions);

        // Branch is crucial for POS
        $branch = Branch::create(['id' => uniqid(), 'name' => 'Sucursal Central', 'address' => 'Plaza', 'active' => true]);

        $user = User::factory()->create([
            'role' => $role->name, 
            'active' => true,
            'branch_id' => $branch->id
        ]);

        $user->branches()->attach($branch->id);

        return $user;
    }

    public function test_can_open_a_cash_register()
    {
        $user = $this->createAuthorizedUser(['manage-cash-registers']);
        Sanctum::actingAs($user);

        $payload = [
            'branch_id' => $user->branch_id,
            'opening_amount' => 1500.50,
            'opening_balances' => ['MXN' => 1500.50, 'USD' => 0]
        ];

        $response = $this->postJson('/api/cash-registers/open', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('cash_registers', [
            'branch_id' => $user->branch_id,
            'opening_amount' => 1500.50,
            'status' => 'open'
        ]);
    }

    public function test_cannot_open_two_registers_simultaneously_in_same_branch()
    {
        $user = $this->createAuthorizedUser(['manage-cash-registers']);
        Sanctum::actingAs($user);

        // First open
        $this->postJson('/api/cash-registers/open', [
            'branch_id' => $user->branch_id,
            'opening_amount' => 500
        ]);

        // Second open attempt
        $response = $this->postJson('/api/cash-registers/open', [
            'branch_id' => $user->branch_id,
            'opening_amount' => 500
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['message' => 'A shift is already open for this branch']);
    }

    public function test_master_z_report_closure_math()
    {
        $user = $this->createAuthorizedUser(['manage-cash-registers', 'view-cash-registers', 'reopen-cash-registers']);
        Sanctum::actingAs($user);

        // 1. Open Register with 1000 MXN base
        $register = CashRegister::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'opening_amount' => 1000,
            'opening_balances' => ['MXN' => 1000, 'USD' => 100],
            'status' => 'open'
        ]);

        // 2. Perform a Sale entirely in MXN Cash (1500 MXN) + a Card Payment (2000 MXN)
        $sale1 = Sale::create([
            'id' => uniqid(),
            'branch_id' => $user->branch_id,
            'cash_register_id' => $register->id,
            'user_id' => $user->id,
            'date' => now(),
            'total_amount' => 3500,
            'status' => 'completed',
            'payments' => [] 
        ]);
        SalePayment::create(['id' => uniqid(), 'sale_id' => $sale1->id, 'method' => 'cash', 'amount' => 1500, 'amount_mxn' => 1500, 'currency' => 'MXN']);
        // This transaction shouldn't affect the cash drawer expected balance
        SalePayment::create(['id' => uniqid(), 'sale_id' => $sale1->id, 'method' => 'card', 'amount' => 2000, 'amount_mxn' => 2000, 'currency' => 'MXN']);

        // 3. Perform a Sale mixed (500 MXN Cash, 50 USD Cash)
        $sale2 = Sale::create([
            'id' => uniqid(),
            'branch_id' => $user->branch_id,
            'cash_register_id' => $register->id,
            'user_id' => $user->id,
            'date' => now(),
            'total_amount' => 1500, // equivalent, not used in expected calc usually, just payment
            'status' => 'completed',
            'payments' => [] 
        ]);
        SalePayment::create(['id' => uniqid(), 'sale_id' => $sale2->id, 'method' => 'cash', 'amount' => 500, 'amount_mxn' => 500, 'currency' => 'MXN']);
        SalePayment::create(['id' => uniqid(), 'sale_id' => $sale2->id, 'method' => 'cash', 'amount' => 50, 'amount_mxn' => 1000, 'currency' => 'USD']);

        // 4. Register a Cash Entry (+200 MXN)
        CashMovement::create([
            'cash_register_id' => $register->id,
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'type' => 'in',
            'amount' => 200,
            'amount_mxn' => 200,
            'currency' => 'MXN',
        ]);

        // 5. Register a Cash Out (-150 MXN for gas)
        CashMovement::create([
            'cash_register_id' => $register->id,
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'type' => 'out',
            'amount' => 150,
            'amount_mxn' => 150,
            'currency' => 'MXN',
        ]);

        // THE MATH EXPECTATION:
        // MXN: Base 1000 + Sale 1500 + Sale 500 + Entry 200 - Out 150 = 3050 MXN
        // USD: Base 100 + Sale 50 = 150 USD

        // Query point
        $currentResponse = $this->getJson('/api/cash-registers/current?branch_id=' . $user->branch_id);
        $currentResponse->assertStatus(200);
        
        $expectedMXN = 1000 + 1500 + 500 + 200 - 150;
        $expectedUSD = 100 + 50;

        $this->assertEquals($expectedMXN, $currentResponse->json('expected_balances.MXN'));
        $this->assertEquals($expectedUSD, $currentResponse->json('expected_balances.USD'));

        // Close the register successfully with exactly that amount
        $closeResponse = $this->postJson("/api/cash-registers/{$register->id}/close", [
            'closing_amount' => $expectedMXN, // main currency representation
            'closing_balances' => ['MXN' => $expectedMXN, 'USD' => $expectedUSD],
            'notes' => 'Perfectly balanced'
        ]);

        $closeResponse->assertStatus(200);
        $this->assertDatabaseHas('cash_registers', [
            'id' => $register->id,
            'status' => 'closed',
            'difference' => 0,
        ]);

        // 6. REOPEN TEST
        // Non-admin reopened check (e.g., if we wanted to enforce role, but currently it's just based on request)
        $reopenResponse = $this->postJson("/api/cash-registers/{$register->id}/reopen");
        $reopenResponse->assertStatus(200);
        
        $this->assertDatabaseHas('cash_registers', [
            'id' => $register->id,
            'status' => 'open',
            'closing_amount' => null,
            'closed_at' => null
        ]);
    }
}
