<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('sale_id');
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
            $table->enum('method', ['cash', 'card', 'transfer']);
            $table->enum('currency', ['MXN', 'USD', 'EUR', 'CAD'])->default('MXN');
            $table->decimal('amount', 12, 2);        // Amount in the original currency
            $table->decimal('amount_mxn', 12, 2);    // Equivalent in MXN at time of sale
            $table->decimal('exchange_rate', 10, 4)->default(1); // Rate used for conversion
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
