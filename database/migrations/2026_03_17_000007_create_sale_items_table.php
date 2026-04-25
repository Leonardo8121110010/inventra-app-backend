<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->string('sale_id');
            $table->string('product_id');
            $table->integer('qty')->default(1);
            $table->decimal('price', 10, 2)->default(0);          // MXN
            $table->decimal('price_in_currency', 10, 4)->default(0);
            $table->enum('currency', ['MXN', 'USD', 'EUR', 'CAD'])->default('MXN');
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
