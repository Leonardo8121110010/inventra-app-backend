<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            UserSeeder::class,
            RolePermissionSeeder::class,
            MenuItemSeeder::class,
            RoleMenuSeeder::class,
            CashMovementMotiveSeeder::class,
            AgentTypeSeeder::class,
            ReferralAgentSeeder::class,
            ArticleSeeder::class,
            InventorySeeder::class,
            CommissionRuleSeeder::class,
            ExchangeRateSeeder::class,
        ]);
    }
}