<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->enum('type', ['ENTRY', 'EXIT', 'SALE', 'ADJUSTMENT', 'TRANSFER_IN', 'TRANSFER_OUT']);
            $table->string('product_id');
            $table->string('branch_id');
            $table->integer('qty'); // negative for exits/sales
            $table->timestamp('date');
            $table->string('user')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
