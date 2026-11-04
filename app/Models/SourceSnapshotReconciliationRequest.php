<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceSnapshotReconciliationRequest extends Model
{
    protected $fillable = [
        'source_id',
        'triggering_snapshot_id',
        'current_snapshot_id',
        'expected_previous_snapshot_id',
        'recorded_previous_snapshot_id',
        'identity_key',
        'status',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function triggeringSnapshot(): BelongsTo
    {
        return $this->belongsTo(SourceSnapshot::class, 'triggering_snapshot_id');
    }

    public function currentSnapshot(): BelongsTo
    {
        return $this->belongsTo(SourceSnapshot::class, 'current_snapshot_id');
    }

    public function expectedPreviousSnapshot(): BelongsTo
    {
        return $this->belongsTo(SourceSnapshot::class, 'expected_previous_snapshot_id');
    }

    public function recordedPreviousSnapshot(): BelongsTo
    {
        return $this->belongsTo(SourceSnapshot::class, 'recorded_previous_snapshot_id');
    }
}
