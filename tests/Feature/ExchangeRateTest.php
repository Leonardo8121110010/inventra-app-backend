<?php

namespace Tests\Feature;

use App\Models\ExchangeRate;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExchangeRateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create an authorized user with the necessary permissions.
     */
    protected function createAuthorizedUser(array $permissions = [])
    {
        $role = Role::create(['name' => 'test-role-' . uniqid(), 'guard_name' => 'web']);
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        
        $role->syncPermissions($permissions);

        return User::factory()->create(['role' => $role->name, 'active' => true]);
    }

    public function test_can_get_available_currencies()
    {
        $user = $this->createAuthorizedUser(['view-exchange-rates']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/exchange-rates/available');
        
        $response->assertStatus(200);
        // It returns data conforming basically to an array/json structure.
        $this->assertIsArray($response->json());
    }

    public function test_can_get_all_saved_exchange_rates()
    {
        $user = $this->createAuthorizedUser(['view-exchange-rates']);
        Sanctum::actingAs($user);

        ExchangeRate::create([
            'currency' => 'EUR',
            'rate' => 20.5,
            'is_live' => false,
        ]);

        $response = $this->getJson('/api/exchange-rates');
        
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'currency' => 'EUR',
            // Float precision can sometimes mess up assertions if strict so we just assert fragment
        ]);
    }

    public function test_can_manually_store_exchange_rate()
    {
        $user = $this->createAuthorizedUser(['manage-exchange-rates']);
        Sanctum::actingAs($user);

        $payload = [
            'currency' => 'CAD',
            'rate' => 15.3,
            'is_live' => false
        ];

        $response = $this->postJson('/api/exchange-rates', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('exchange_rates', [
            'currency' => 'CAD',
            'rate' => 15.3,
            'is_live' => 0
        ]);
    }

    public function test_validation_fails_when_storing_invalid_rate()
    {
        $user = $this->createAuthorizedUser(['manage-exchange-rates']);
        Sanctum::actingAs($user);

        $payload = [
            'currency' => 'CAD',
            'rate' => -5, // Invalid negative rate
            'is_live' => false
        ];

        $response = $this->postJson('/api/exchange-rates', $payload);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rate']);
    }

    public function test_can_delete_exchange_rate()
    {
        $user = $this->createAuthorizedUser(['manage-exchange-rates']);
        Sanctum::actingAs($user);

        ExchangeRate::create([
            'currency' => 'GBP',
            'rate' => 22.1,
            'is_live' => false,
        ]);

        $response = $this->deleteJson('/api/exchange-rates/GBP');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('exchange_rates', [
            'currency' => 'GBP'
        ]);
    }

    public function test_unauthorized_user_cannot_manage_exchange_rates()
    {
        // User with no permissions
        $user = $this->createAuthorizedUser([]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/exchange-rates', [
            'currency' => 'USD',
            'rate' => 18.0,
            'is_live' => false
        ]);

        $response->assertStatus(403);
    }
}
