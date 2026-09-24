<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SourceRetrievalRun extends Model {
 protected $fillable=['source_id','status','http_status','content_type','content_length','content_hash','snapshot_id','error_message','started_at','completed_at'];
 protected $casts=['started_at'=>'datetime','completed_at'=>'datetime'];
 public function source(): BelongsTo { return $this->belongsTo(Source::class); }
 public function snapshot(): BelongsTo { return $this->belongsTo(SourceSnapshot::class); }
}