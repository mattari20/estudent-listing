<?php

namespace App\Models;

use App\Models\Concerns\PreventsAuditMutation;

use Illuminate\Database\Eloquent\Model;

class SecurityAuditEvent extends Model
{
    use PreventsAuditMutation;
    protected $fillable = ['event_type', 'actor_type', 'actor_id', 'subject_type', 'subject_id', 'severity', 'description', 'request_id', 'monitoring_idempotency_key'];
}