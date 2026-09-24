<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class MonitoringAlert extends Model {
 protected $fillable=['source_monitoring_policy_id','source_id','alert_type','severity','status','dedupe_key','message','details','first_detected_at','last_detected_at','acknowledged_at','resolved_at'];
 protected $casts=['details'=>'array','first_detected_at'=>'datetime','last_detected_at'=>'datetime','acknowledged_at'=>'datetime','resolved_at'=>'datetime'];
 public function policy(): BelongsTo { return $this->belongsTo(SourceMonitoringPolicy::class,'source_monitoring_policy_id'); }
 public function source(): BelongsTo { return $this->belongsTo(Source::class); }
}