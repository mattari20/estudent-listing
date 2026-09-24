<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ImportReconciliationEvent extends Model {
 protected $fillable=['import_batch_id','import_row_id','event_type','state','severity','subject_type','subject_id','description','metadata','actor_type','actor_id','occurred_at'];
 protected $casts=['metadata'=>'array','occurred_at'=>'datetime'];
 public function batch(): BelongsTo { return $this->belongsTo(ImportBatch::class,'import_batch_id'); }
 public function row(): BelongsTo { return $this->belongsTo(ImportRow::class,'import_row_id'); }
}