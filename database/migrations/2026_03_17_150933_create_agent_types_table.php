<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_types', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        // Defaults now handled by seeders

        // Add foreign key by first renaming the old type column or just converting it.
        // But enum in postgres can't be just changed to string easily.
        // Let's create a new column, migrate data, drop old column, rename new.
        Schema::table('referral_agents', function (Blueprint $table) {
            $table->string('agent_type_id')->nullable();
        });

        // Copy data:
        // 'type' is old enum. In SQLite we don't need casting, in Postgres we might.
        $casting = DB::getDriverName() === 'pgsql' ? '::varchar' : '';
        DB::statement("UPDATE referral_agents SET agent_type_id = type{$casting}");

        // Since we've seeded, the type column values match the ids in agent_types.
        Schema::table('referral_agents', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('referral_agents', function (Blueprint $table) {
            $table->renameColumn('agent_type_id', 'type');
        });

        Schema::table('referral_agents', function (Blueprint $table) {
            $table->foreign('type')->references('id')->on('agent_types')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('referral_agents', function (Blueprint $table) {
            $table->dropForeign(['type']);
        });

        Schema::dropIfExists('agent_types');
    }
};
