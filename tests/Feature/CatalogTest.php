<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setupAdmin()
    {
        $role = Role::create(['name' => 'admin-' . uniqid(), 'guard_name' => 'web']);
        $perms = [
            'view-articles', 'create-articles', 'edit-articles', 'delete-articles',
            'view-suppliers', 'create-suppliers', 'edit-suppliers', 'delete-suppliers',
            'view-branches', 'create-branches', 'edit-branches', 'delete-branches'
        ];
        foreach ($perms as $p) {
            Permission::create(['name' => $p, 'guard_name' => 'web']);
        }
        $role->syncPermissions($perms);

        $user = User::factory()->create(['role' => $role->name, 'active' => true]);
        Sanctum::actingAs($user);
        return $user;
    }

    public function test_article_crud_flow()
    {
        $this->setupAdmin();
        $product = Product::create(['id' => 'p-cat', 'name' => 'Category']);
        $supplier = Supplier::create(['id' => 'sup-001', 'name' => 'Main Supplier', 'active' => true]);

        // 1. Create
        $payload = [
            'id' => 'art_new',
            'name' => 'New Article',
            'sku' => 'SKU-001',
            'category_id' => $product->id,
            'supplier_id' => $supplier->id,
            'price' => 120.50,
            'cost' => 80.00,
            'size_ml' => 750,
            'active' => true
        ];

        $response = $this->postJson('/api/articles', $payload);
        $response->assertStatus(201);
        $articleId = $response->json('id');

        // 2. Read
        $this->getJson('/api/articles')->assertStatus(200)->assertJsonFragment(['sku' => 'SKU-001']);

        // 3. Update
        $this->putJson("/api/articles/{$articleId}", ['name' => 'Updated Name', 'price' => 130])
             ->assertStatus(200);

        $this->assertDatabaseHas('products', ['id' => $articleId, 'name' => 'Updated Name', 'price' => 130]);

        // 4. Delete (Soft Deactivate)
        $this->deleteJson("/api/articles/{$articleId}")->assertStatus(200);
        $this->assertDatabaseHas('products', ['id' => $articleId, 'active' => false]);
    }

    public function test_supplier_crud_flow()
    {
        $this->setupAdmin();

        $payload = ['id' => 'sup_new', 'name' => 'New Supplier', 'phone' => '123456789', 'email' => 'tech@supplier.com'];
        
        $response = $this->postJson('/api/suppliers', $payload);
        $response->assertStatus(201);
        $supplierId = $response->json('id');

        $this->putJson("/api/suppliers/{$supplierId}", ['name' => 'Modified Supplier'])
             ->assertStatus(200);

        $this->assertDatabaseHas('suppliers', ['id' => $supplierId, 'name' => 'Modified Supplier']);
    }

    public function test_branch_crud_flow()
    {
        $this->setupAdmin();

        $payload = ['id' => 'br_new', 'name' => 'New Branch', 'type' => 'sucursal', 'address' => 'Mall 1'];

        $response = $this->postJson('/api/branches', $payload);
        $response->assertStatus(201);

        $this->getJson('/api/branches')->assertStatus(200)->assertJsonFragment(['id' => 'br_new']);
        
        $this->deleteJson("/api/branches/br_new")->assertStatus(204);
    }
}
