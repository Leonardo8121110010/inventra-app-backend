<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->string('menu_key');
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->unique(['role_id', 'menu_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_menus');
    }
};
