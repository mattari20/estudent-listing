<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EnrichmentImportRow extends Model {
 protected $fillable=['import_batch_id','entity_type','canonical_subject_id','status','match_state','payload','normalized_payload','validation_errors'];
 protected $casts=['payload'=>'array','normalized_payload'=>'array','validation_errors'=>'array'];
 public function batch(): BelongsTo { return $this->belongsTo(ImportBatch::class,'import_batch_id'); }
}