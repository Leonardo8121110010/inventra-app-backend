<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('cash_register_id');
            $table->string('branch_id');
            $table->unsignedBigInteger('user_id');
            $table->string('type'); // 'in' | 'out'
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_mxn', 10, 2)->default(0);
            $table->string('currency', 10)->default('MXN');
            $table->string('motive_id')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('sale_id')->nullable();
            $table->string('referral_agent_id')->nullable();
            $table->timestamps();

            $table->foreign('cash_register_id')->references('id')->on('cash_registers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('motive_id')->references('id')->on('cash_movement_motives')->nullOnDelete();
            $table->foreign('sale_id')->references('id')->on('sales')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
