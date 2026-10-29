<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionFieldApproval extends Model
{
    protected $fillable = [
        'institution_id', 'field_name', 'expected_current_value', 'proposed_value',
        'source_id', 'source_change_observation_id', 'source_snapshot_id', 'evidence_id',
        'evidence_url', 'evidence_excerpt', 'content_fingerprint',
        'source_observed_at', 'evidence_observed_at', 'proposal_hash', 'status',
        'created_by', 'reviewer_id', 'decision_at', 'decision_reason',
        'applied_at', 'application_outcome', 'applied_refresh_event_id',
    ];

    protected $casts = [
        'source_observed_at' => 'datetime',
        'evidence_observed_at' => 'datetime',
        'decision_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function source(): BelongsTo { return $this->belongsTo(Source::class); }
    public function observation(): BelongsTo { return $this->belongsTo(SourceChangeObservation::class, 'source_change_observation_id'); }
    public function snapshot(): BelongsTo { return $this->belongsTo(SourceSnapshot::class, 'source_snapshot_id'); }
    public function evidence(): BelongsTo { return $this->belongsTo(Evidence::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }
    public function refreshEvent(): BelongsTo { return $this->belongsTo(OfficialSourceRefreshEvent::class, 'applied_refresh_event_id'); }
}
