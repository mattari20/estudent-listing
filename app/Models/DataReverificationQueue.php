<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DataReverificationQueue extends Model {
 protected $table='data_reverification_queue';
 protected $fillable=['subject_type','subject_id','attribute','reason','priority','status','source_id','evidence_id','observed_at','due_at','assigned_to_type','assigned_to_id','notes','resolved_at'];
 protected $casts=['observed_at'=>'datetime','due_at'=>'datetime','resolved_at'=>'datetime'];
 public function source(): BelongsTo { return $this->belongsTo(Source::class); }
 public function evidence(): BelongsTo { return $this->belongsTo(Evidence::class); }
}