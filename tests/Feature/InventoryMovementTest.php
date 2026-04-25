<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Inventory;

class InventoryMovementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch1;
    protected $branch2;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->branch1 = Branch::create(['id' => 'b1', 'name' => 'Branch 1']);
        $this->branch2 = Branch::create(['id' => 'b2', 'name' => 'Branch 2']);
        $category = \App\Models\Product::create(['id' => 'cat1', 'name' => 'Cat']);
        $this->product = \App\Models\Article::create(['id' => 'p1', 'name' => 'Product 1', 'category_id' => 'cat1', 'sku' => 'sku', 'cost' => 0, 'price' => 0, 'size_ml' => 0, 'min_stock' => 0, 'active' => 1]);
    }

    public function test_it_allows_valid_entry()
    {
        $response = $this->actingAs($this->user)->postJson('/api/movements', [
            'type' => 'ENTRY',
            'product_id' => $this->product->id,
            'branch_id' => $this->branch1->id,
            'qty' => 10
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('inventory', [
            'product_id' => $this->product->id,
            'branch_id' => $this->branch1->id,
            'qty' => 10
        ]);
    }

    public function test_it_prevents_negative_stock_on_exit()
    {
        $this->actingAs($this->user)->postJson('/api/movements', [
            'type' => 'EXIT',
            'product_id' => $this->product->id,
            'branch_id' => $this->branch1->id,
            'qty' => -5
        ])->assertStatus(422); // Should trigger insufficient stock or validation error
    }

    public function test_it_validates_sign_of_qty_based_on_type()
    {
        // An attacker tries to send an EXIT with a POSITIVE quantity to increase stock without authorization
        $response = $this->actingAs($this->user)->postJson('/api/movements', [
            'type' => 'EXIT',
            'product_id' => $this->product->id,
            'branch_id' => $this->branch1->id,
            'qty' => 50
        ]);
        
        // This should theoretically be rejected by validation
        $response->assertStatus(422); 
    }
    
    public function test_it_validates_entry_with_negative_qty()
    {
        // Attacker tries to send an ENTRY with negative qty 
        $this->actingAs($this->user)->postJson('/api/movements', [
            'type' => 'ENTRY',
            'product_id' => $this->product->id,
            'branch_id' => $this->branch1->id,
            'qty' => 10
        ]); // add 10 first
        
        $response = $this->actingAs($this->user)->postJson('/api/movements', [
            'type' => 'ENTRY',
            'product_id' => $this->product->id,
            'branch_id' => $this->branch1->id,
            'qty' => -5
        ]);

        $response->assertStatus(422);
    }
}
