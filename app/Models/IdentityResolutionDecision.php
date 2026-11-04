<?php

namespace App\Models;

use App\Models\Concerns\ProtectsIdentityDecisionHistory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityResolutionDecision extends Model
{
    use ProtectsIdentityDecisionHistory;
    protected $fillable = [
        'import_row_id',
        'institution_id',
        'decision_identity_key',
        'decision',
        'actor_type',
        'actor_id',
        'reason',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function importRow(): BelongsTo
    {
        return $this->belongsTo(ImportRow::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
