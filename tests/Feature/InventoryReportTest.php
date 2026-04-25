<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Movement;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_inventory_reconstruction_logic()
    {
        // 1. Setup Base Data
        $branch = Branch::create(['id' => 'br-report', 'name' => 'Report Branch']);
        $user = User::factory()->create(['branch_id' => $branch->id, 'role' => 'admin', 'active' => true]);
        Sanctum::actingAs($user);

        $product = Product::create(['id' => 'p-gin', 'name' => 'Gin']);
        $article = Article::create([
            'id' => 'art-gin',
            'name' => 'Gin Test',
            'sku' => 'GIN001',
            'category_id' => $product->id,
            'price' => 500,
            'cost' => 200,
            'total_cost' => 200,
            'active' => true
        ]);

        // ── SCENARIO ─────────────────────────────────────────────────────────
        // Target Date: Today
        // 1. We have 10 units NOW (live stock).
        // 2. Tomorrow, we will receive 5 units (future movement).
        // 3. Today, we sold 2 units (current date movement).
        // 4. Today, we had 1 unit for tasting (current date negative adjustment).
        // 
        // EXPECTED for Today:
        // Final Today = Live(10) - Future(+5) = 5
        // Inicio Today = Final(5) - TodayMovs(+0) + TodaySales(2) + TodayTasting(1) = 8
        // ─────────────────────────────────────────────────────────────────────

        $targetDate = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        // Current Inventory
        Inventory::create(['branch_id' => $branch->id, 'product_id' => $article->id, 'qty' => 10]);

        // Future Movement (Tomorrow)
        Movement::create([
            'id' => 'mv-future',
            'type' => 'ENTRY',
            'product_id' => $article->id,
            'branch_id' => $branch->id,
            'qty' => 5,
            'date' => $tomorrow->copy()->setTime(10, 0),
            'user' => 'Admin'
        ]);

        // Today's Movements
        Movement::create([
            'id' => 'mv-sale',
            'type' => 'SALE',
            'product_id' => $article->id,
            'branch_id' => $branch->id,
            'qty' => -2,
            'date' => $targetDate->copy()->setTime(12, 0),
            'user' => 'Cashier'
        ]);

        Movement::create([
            'id' => 'mv-tasting',
            'type' => 'ADJUSTMENT',
            'product_id' => $article->id,
            'branch_id' => $branch->id,
            'qty' => -1,
            'notes' => 'Tasting for customers',
            'date' => $targetDate->copy()->setTime(14, 0),
            'user' => 'Admin'
        ]);

        // Execute Report Request
        $response = $this->getJson("/api/reports/daily-inventory?date=" . $targetDate->toDateString() . "&branch_id=" . $branch->id);

        $response->assertStatus(200);
        
        $data = collect($response->json('articles'))->firstWhere('article_id', $article->id);

        $this->assertNotNull($data, "Article not found in report");
        $this->assertEquals(8, $data['inicio'], "Inicio stock calculation failed");
        $this->assertEquals(5, $data['final'], "Final stock calculation failed");
        $this->assertEquals(2, $data['ventas'], "Sales count failed");
        $this->assertEquals(1, $data['tasting'], "Tasting count failed");
    }
}
