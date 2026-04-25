<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralAgent extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'name', 'phone', 'status'];
    protected $with = ['agentTypes'];

    public function agentTypes()
    {
        return $this->belongsToMany(AgentType::class, 'agent_type_referral_agent', 'referral_agent_id', 'agent_type_id');
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class, 'agent_id');
    }
}
