<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialSourceRefreshEvent extends Model
{
    protected $fillable = [
        'source_id',
        'source_change_observation_id',
        'canonical_type',
        'canonical_id',
        'field_name',
        'before_value',
        'after_value',
        'evidence_url',
        'evidence_excerpt',
        'content_fingerprint',
        'observed_at',
        'occurred_at',
    ];

    protected $casts = [
        'before_value' => 'array',
        'after_value' => 'array',
        'observed_at' => 'datetime',
        'occurred_at' => 'datetime',
    ];
}
