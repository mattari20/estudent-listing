<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SourceMonitoringPolicy extends Model {
 protected $fillable=['name','subject_type','attribute','fresh_after_days','stale_after_days','priority','status','source_id','last_run_at','cadence','next_run_at','last_success_at','last_failure_at','consecutive_failures'];
 protected $casts=['last_run_at'=>'datetime','next_run_at'=>'datetime','last_success_at'=>'datetime','last_failure_at'=>'datetime'];
 public function source(): BelongsTo { return $this->belongsTo(Source::class); }
}