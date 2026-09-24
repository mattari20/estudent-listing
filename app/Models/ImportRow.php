<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportRow extends Model
{
    protected $fillable = [
        'import_batch_id', 'row_number', 'status', 'match_state',
        'canonical_subject_type', 'canonical_subject_id',
        'parent_subject_type', 'parent_subject_id',
        'payload', 'normalized_payload', 'validation_errors', 'notes',
    ];

    protected $casts = ['payload' => 'array', 'normalized_payload' => 'array', 'validation_errors' => 'array'];

    public function batch(): BelongsTo { return $this->belongsTo(ImportBatch::class, 'import_batch_id'); }

    public function promotionEvents(): HasMany { return $this->hasMany(ImportPromotionEvent::class); }
}
