<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StructuredEnrichmentPromotionEvent extends Model
{
    protected $fillable = [
        'enrichment_import_row_id',
        'import_batch_id',
        'entity_type',
        'event_type',
        'canonical_type',
        'canonical_id',
        'actor_type',
        'actor_id',
        'reason',
        'before_snapshot',
        'after_snapshot',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'before_snapshot' => 'array',
        'after_snapshot' => 'array',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function enrichmentRow(): BelongsTo
    {
        return $this->belongsTo(EnrichmentImportRow::class, 'enrichment_import_row_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
