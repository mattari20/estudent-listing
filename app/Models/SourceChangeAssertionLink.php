<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SourceChangeAssertionLink extends Model {
 protected $fillable=['source_change_observation_id','assertion_id','attribute','impact','status','reason'];
 public function observation(): BelongsTo { return $this->belongsTo(SourceChangeObservation::class,'source_change_observation_id'); }
 public function assertion(): BelongsTo { return $this->belongsTo(Assertion::class); }
}