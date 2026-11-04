<?php
namespace App\Models;

use App\Models\Concerns\PreventsAuditMutation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ImportPromotionDecision extends Model {
    use PreventsAuditMutation;
 protected $fillable=['import_row_id','decision','actor_type','actor_id','reason','decided_at'];
 protected $casts=['decided_at'=>'datetime'];
 public function importRow(): BelongsTo { return $this->belongsTo(ImportRow::class); }
}