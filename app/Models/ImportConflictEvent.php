<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportConflictEvent extends Model
{
    protected $fillable = [
        'import_conflict_id',
        'import_row_id',
        'event_type',
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

    public function importRow(): BelongsTo
    {
        return $this->belongsTo(ImportRow::class);
    }
}
