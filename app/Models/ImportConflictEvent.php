<?php

namespace App\Models;

use App\Models\Concerns\PreventsAuditMutation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportConflictEvent extends Model
{
    use PreventsAuditMutation;
    protected $fillable = [
        'import_conflict_id',
        'event_type',
        'status_before',
        'status_after',
        'actor_type',
        'actor_id',
        'reason',
        'metadata',
        'idempotency_key',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function conflict(): BelongsTo
    {
        return $this->belongsTo(ImportConflict::class, 'import_conflict_id');
    }
}
