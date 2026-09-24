<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ReverificationEvent extends Model {
 protected $fillable=['queue_id','event_type','actor_type','actor_id','previous_status','new_status','evidence_id','reason','metadata','occurred_at'];
 protected $casts=['metadata'=>'array','occurred_at'=>'datetime'];
 public function queue(): BelongsTo { return $this->belongsTo(DataReverificationQueue::class,'queue_id'); }
 public function evidence(): BelongsTo { return $this->belongsTo(Evidence::class); }
}