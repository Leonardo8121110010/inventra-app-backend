<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('branch_id');
            $table->string('user_id');
            $table->timestamp('date');
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('total_mxn', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->enum('payment', ['cash', 'card', 'transfer'])->nullable()->default('cash');
            $table->enum('currency', ['MXN', 'USD', 'EUR', 'CAD'])->default('MXN');
            $table->string('referral_agent_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
