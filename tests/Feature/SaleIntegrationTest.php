<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ReferralAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setupPrerequisites()
    {
        $branch = Branch::create(['id' => 'br-test', 'name' => 'Test Branch']);
        $user = User::factory()->create(['branch_id' => $branch->id, 'role' => 'admin', 'active' => true]);
        $user->branches()->attach($branch->id);

        $register = CashRegister::create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'opening_amount' => 1000,
            'status' => 'open',
            'opened_at' => now()
        ]);

        $product = Product::create(['id' => 'p-liquor', 'name' => 'Liquor', 'icon' => 'bottle']);
        
        $article = Article::create([
            'id' => 'art-001',
            'name' => 'Tequila Test',
            'sku' => 'TEQ001',
            'category_id' => $product->id,
            'price' => 100, // Price in MXN
            'cost' => 50,
            'total_cost' => 50,
            'active' => true
        ]);

        Inventory::create(['branch_id' => $branch->id, 'product_id' => $article->id, 'qty' => 10]);

        return compact('user', 'branch', 'register', 'article');
    }

    public function test_full_sale_process_with_stock_and_commissions()
    {
        $data = $this->setupPrerequisites();
        Sanctum::actingAs($data['user']);

        $agent = ReferralAgent::create(['id' => 'ag-001', 'name' => 'John Doe', 'status' => 'active']);
        
        // Define a 10% commission rule for this agent
        CommissionRule::create([
            'id' => 'rule-pct',
            'agent_id' => $agent->id,
            'trigger' => 'sale',
            'commission_type' => 'percentage',
            'value' => 10,
            'active' => true
        ]);

        $payload = [
            'id' => 'sale-' . uniqid(),
            'branch_id' => $data['branch']->id,
            'date' => now()->toISOString(),
            'total' => 200,
            'total_mxn' => 200,
            'currency' => 'MXN',
            'referral_agents' => [
                ['agent_id' => $agent->id]
            ],
            'payments' => [
                [
                    'method' => 'cash',
                    'currency' => 'MXN',
                    'amount' => 200,
                    'amount_mxn' => 200,
                    'exchange_rate' => 1
                ]
            ],
            'items' => [
                [
                    'product_id' => $data['article']->id,
                    'qty' => 2,
                    'price' => 100,
                    'currency' => 'MXN'
                ]
            ]
        ];

        $response = $this->postJson('/api/sales', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('total', 200);

        // 1. Check Inventory Reduction
        $this->assertDatabaseHas('inventory', [
            'branch_id' => $data['branch']->id,
            'product_id' => $data['article']->id,
            'qty' => 8 // 10 - 2
        ]);

        // 2. Check Movement Log
        $this->assertDatabaseHas('movements', [
            'type' => 'SALE',
            'product_id' => $data['article']->id,
            'qty' => -2
        ]);

        // 3. Check Commission Generation (10% of 200 = 20)
        $this->assertDatabaseHas('commissions', [
            'agent_id' => $agent->id,
            'sale_amount' => 200,
            'commission_amount' => 20,
            'status' => 'pending'
        ]);
    }

    public function test_volume_bonus_commission()
    {
        $data = $this->setupPrerequisites();
        Sanctum::actingAs($data['user']);

        $agent = ReferralAgent::create(['id' => 'ag-vol', 'name' => 'Volume Agent', 'status' => 'active']);
        
        // Bonus of 50 MXN if 3 bottles sold in a month
        CommissionRule::create([
            'id' => 'rule-vol',
            'agent_id' => $agent->id,
            'trigger' => 'volume',
            'commission_type' => 'fixed',
            'value' => 50,
            'volume_threshold' => 3,
            'period' => 'monthly',
            'active' => true
        ]);

        $saleId = 'sale-vol-1';
        $payload = [
            'id' => $saleId,
            'branch_id' => $data['branch']->id,
            'date' => now()->toISOString(),
            'total' => 300,
            'total_mxn' => 300,
            'currency' => 'MXN',
            'referral_agents' => [['agent_id' => $agent->id]],
            'payments' => [['method' => 'cash', 'currency' => 'MXN', 'amount' => 300, 'amount_mxn' => 300]],
            'items' => [['product_id' => $data['article']->id, 'qty' => 3, 'price' => 100, 'currency' => 'MXN']]
        ];

        $this->postJson('/api/sales', $payload)->assertStatus(201);

        // Check for Volume Bonus Commission
        $this->assertDatabaseHas('commissions', [
            'agent_id' => $agent->id,
            'sale_id' => $saleId,
            'commission_amount' => 50,
            'status' => 'pending',
            'rule_id' => 'rule-vol'
        ]);
    }
}
