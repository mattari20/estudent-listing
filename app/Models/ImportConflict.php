<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ImportConflict extends Model {
 protected $fillable=['import_row_id','import_batch_id','conflict_type','severity','status','description','details','related_row_id','related_subject_type','related_subject_id','resolved_by_type','resolved_by_id','resolution_reason','resolved_at'];
 protected $casts=['details'=>'array','resolved_at'=>'datetime'];
 public function importRow(): BelongsTo { return $this->belongsTo(ImportRow::class); }
 public function batch(): BelongsTo { return $this->belongsTo(ImportBatch::class,'import_batch_id'); }
}