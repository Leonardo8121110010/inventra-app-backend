<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot table: user ↔ branches (multi-sucursal).
     * Keeps `users.branch_id` as primary branch for backwards compatibility.
     */
    public function up(): void
    {
        Schema::create('branch_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('branch_id');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->unique(['user_id', 'branch_id']);
        });

        // Migrate existing users.branch_id into the pivot table
        $users = \Illuminate\Support\Facades\DB::table('users')
            ->whereNotNull('branch_id')
            ->where('branch_id', '!=', '')
            ->get();

        foreach ($users as $user) {
            \Illuminate\Support\Facades\DB::table('branch_user')->insert([
                'user_id'    => $user->id,
                'branch_id'  => $user->branch_id,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user');
    }
};
