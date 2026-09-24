<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SourceMonitoringPolicy extends Model {
 protected $fillable=['name','subject_type','attribute','fresh_after_days','stale_after_days','priority','status','source_id','last_run_at'];
 protected $casts=['last_run_at'=>'datetime'];
 public function source(): BelongsTo { return $this->belongsTo(Source::class); }
}