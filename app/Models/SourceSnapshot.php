<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceSnapshot extends Model
{
    protected $fillable = [
        'source_id',
        'reference',
        'content_hash',
        'content_length',
        'content_type',
        'content',
        'captured_at',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'captured_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
