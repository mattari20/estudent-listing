<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ImportPromotionEvent extends Model {
 protected $fillable=['import_row_id','import_batch_id','entity_type','event_type','previous_status','new_status','previous_match_state','new_match_state','canonical_subject_type','canonical_subject_id','actor_type','actor_id','decision_type','decision_id','reason','metadata','occurred_at'];
 protected $casts=['metadata'=>'array','occurred_at'=>'datetime'];
 public function importRow(): BelongsTo { return $this->belongsTo(ImportRow::class); }
 public function batch(): BelongsTo { return $this->belongsTo(ImportBatch::class,'import_batch_id'); }
}