<?php

namespace Database\Seeders;

use App\Models\ReferralAgent;
use App\Models\AgentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferralAgentSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar tablas para evitar duplicados
        DB::table('agent_type_referral_agent')->delete();
        ReferralAgent::query()->delete();

        // 1. VENDEDORES TEQUILERAS
        $vendedores = ['CARLOS', 'MARIO', 'BRANDON', 'EDUARDO', 'VICTOR', 'SANDRA', 'JORGE'];
        foreach ($vendedores as $i => $name) {
            $agent = ReferralAgent::create([
                'id' => 'VEN_' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'name' => $name,
                'status' => 'active'
            ]);
            $agent->agentTypes()->attach('vendedor');
        }

        // 2. GUIAS CASAS (Internos)
        $guiasCasas = [
            'MIGUEL PAT', 'FAISAL', 'ISRAEL', 'LUIS BRITO (LICHO)', 
            'POCHIS DOMINGO', 'JACOBO', 'SANTIAGO', 'RICARDO', 
            'BRUNO', 'OMAR', 'EDGAR/RENE', 'WALTER', 'GIOVANNY'
        ];
        foreach ($guiasCasas as $i => $name) {
            $agent = ReferralAgent::create([
                'id' => 'GINT_' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'name' => $name,
                'status' => 'active'
            ]);
            $agent->agentTypes()->attach('guia_interno');
        }

        // 3. GUIAS EXTERNOS
        $guiasExternos = [
            'ADRIANA', 'ALEX', 'CHARLY', 'DANIEL', 'DAVID TOP', 
            'DIEGO', 'EDWIN', 'ELIZABETH', 'GABRIEL', 'ISLA GO', 
            'ISRAEL', 'ISSAC', 'JORGE', 'MANUELA VAN', 'MAURO', 
            'OSCAR', 'TRIBY', 'VIRIDIANA'
        ];
        foreach ($guiasExternos as $i => $name) {
            $agent = ReferralAgent::create([
                'id' => 'GEXT_' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'name' => $name,
                'status' => 'active'
            ]);
            $agent->agentTypes()->attach('guia_externo');
        }
    }
}
