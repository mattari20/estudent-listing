<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ImportPromotionDecision extends Model {
 protected $fillable=['import_row_id','decision','actor_type','actor_id','reason','decided_at'];
 protected $casts=['decided_at'=>'datetime'];
 public function importRow(): BelongsTo { return $this->belongsTo(ImportRow::class); }
}