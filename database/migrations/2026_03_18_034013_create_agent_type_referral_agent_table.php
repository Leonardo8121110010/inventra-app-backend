<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agent_type_referral_agent', function (Blueprint $table) {
            $table->id();
            $table->string('referral_agent_id');
            $table->string('agent_type_id');
            $table->timestamps();

            $table->foreign('referral_agent_id')->references('id')->on('referral_agents')->onDelete('cascade');
            $table->foreign('agent_type_id')->references('id')->on('agent_types')->onDelete('cascade');

            $table->unique(['referral_agent_id', 'agent_type_id']);
        });

        // Migrate existing data
        \Illuminate\Support\Facades\DB::statement('
            INSERT INTO agent_type_referral_agent (referral_agent_id, agent_type_id, created_at, updated_at)
            SELECT id, type, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP FROM referral_agents WHERE type IS NOT NULL
        ');

        // Drop old column
        Schema::table('referral_agents', function (Blueprint $table) {
            $table->dropForeign(['type']);
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('referral_agents', function (Blueprint $table) {
            $table->string('type')->nullable();
            $table->foreign('type')->references('id')->on('agent_types')->onDelete('restrict');
        });

        \Illuminate\Support\Facades\DB::statement('
            UPDATE referral_agents ra
            SET type = (
                SELECT agent_type_id FROM agent_type_referral_agent atra 
                WHERE atra.referral_agent_id = ra.id LIMIT 1
            )
        ');

        Schema::dropIfExists('agent_type_referral_agent');
    }
};
