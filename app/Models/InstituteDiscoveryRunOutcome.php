<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class InstituteDiscoveryRunOutcome extends Model
{
    protected $fillable = [
        'source_id',
        'source_change_observation_id',
        'snapshot_id',
        'profile_key',
        'outcome',
        'candidate_count',
        'review_package_status',
        'failure_class',
        'details',
        'occurred_at',
    ];

    protected $casts = [
        'details' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function observation(): BelongsTo
    {
        return $this->belongsTo(SourceChangeObservation::class, 'source_change_observation_id');
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(SourceSnapshot::class, 'snapshot_id');
    }
}
