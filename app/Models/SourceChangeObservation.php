<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class SourceChangeObservation extends Model {
 protected $fillable=['source_id','previous_snapshot_id','current_snapshot_id','change_state','materiality','status','summary','details','observed_at'];
 protected $casts=['details'=>'array','observed_at'=>'datetime'];
 public function source(): BelongsTo { return $this->belongsTo(Source::class); }
 public function previousSnapshot(): BelongsTo { return $this->belongsTo(SourceSnapshot::class,'previous_snapshot_id'); }
 public function currentSnapshot(): BelongsTo { return $this->belongsTo(SourceSnapshot::class,'current_snapshot_id'); }
 public function assertionLinks(): HasMany { return $this->hasMany(SourceChangeAssertionLink::class); }
}