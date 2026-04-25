<?php

namespace Tests\Feature;

use App\Models\ExchangeRate;
use App\Models\ExchangeRateHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurrencyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_manually_update_exchange_rate()
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        Sanctum::actingAs($admin);

        $payload = [
            'currency' => 'USD',
            'rate' => 17.50,
            'is_live' => false
        ];

        $response = $this->postJson('/api/exchange-rates', $payload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('exchange_rates', [
            'currency' => 'USD',
            'rate' => 17.50,
            'is_live' => false
        ]);

        // Check history log
        $this->assertDatabaseHas('exchange_rate_histories', [
            'currency' => 'USD',
            'rate' => 17.50,
            'user_id' => $admin->id
        ]);
    }

    public function test_can_fetch_live_rates_with_mock()
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        Sanctum::actingAs($admin);

        // Pre-create some currencies to be updated (live fetch only updates existing ones)
        ExchangeRate::create(['currency' => 'USD', 'rate' => 1.0, 'is_live' => true]);
        ExchangeRate::create(['currency' => 'EUR', 'rate' => 1.0, 'is_live' => true]);

        // Mock the external API
        // If 1 MXN = 0.05 USD, then 1 USD = 20 MXN
        // If 1 MXN = 0.04 EUR, then 1 EUR = 25 MXN
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'result' => 'success',
                'rates' => [
                    'USD' => 0.05,
                    'EUR' => 0.04
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/exchange-rates/fetch-live');
        $response->assertStatus(200);

        $this->assertDatabaseHas('exchange_rates', [
            'currency' => 'USD',
            'rate' => 20.0
        ]);

        $this->assertDatabaseHas('exchange_rates', [
            'currency' => 'EUR',
            'rate' => 25.0
        ]);
    }

    public function test_manual_rate_is_not_overwritten_by_live_fetch()
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        Sanctum::actingAs($admin);

        // This currency is MANUAL (is_live = false)
        ExchangeRate::create(['currency' => 'USD', 'rate' => 50.0, 'is_live' => false]);

        Http::fake([
            'open.er-api.com/*' => Http::response([
                'result' => 'success',
                'rates' => ['USD' => 0.05] // would be 20 MXN
            ], 200)
        ]);

        $this->postJson('/api/exchange-rates/fetch-live');

        // Should still be 50.0
        $this->assertDatabaseHas('exchange_rates', [
            'currency' => 'USD',
            'rate' => 50.0,
            'is_live' => false
        ]);
    }
}
