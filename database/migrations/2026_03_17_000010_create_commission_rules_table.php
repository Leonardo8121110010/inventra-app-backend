<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('agent_id')->default('');  // empty = all agents
            $table->string('agent_type_id')->nullable()->default('');
            $table->string('product_id')->default(''); // empty = all products
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('value', 10, 2)->default(0);
            $table->enum('trigger', ['visit', 'sale', 'volume'])->default('sale');
            $table->integer('volume_threshold')->nullable(); // e.g. 7 for "every 7 bottles"
            $table->enum('period', ['all_time', 'monthly', 'weekly', 'daily'])->default('all_time');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
