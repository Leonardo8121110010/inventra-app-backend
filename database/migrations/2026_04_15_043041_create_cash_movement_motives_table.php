<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movement_motives', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('type')->default('both'); // 'in', 'out', 'both'
            $table->string('icon', 50)->nullable();
            $table->boolean('applies_commission')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movement_motives');
    }
};
