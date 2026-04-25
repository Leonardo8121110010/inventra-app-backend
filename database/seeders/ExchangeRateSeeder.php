<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure USD exists (create or keep existing)
        DB::table('exchange_rates')->updateOrInsert(
            ['currency' => 'USD'],
            [
                'rate'       => 17.00,
                'is_live'    => false,
                'updated_at' => now(),
            ]
        );

        // Remove other currencies (EUR, CAD) that may exist from previous runs.
        // Only USD is the default. Other currencies should be added manually
        // or fetched via the live API.
        DB::table('exchange_rates')
            ->whereNotIn('currency', ['USD', 'MXN'])
            ->delete();
    }
}
