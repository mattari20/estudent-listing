<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class VerificationDecision extends Model {
 protected $fillable=['assertion_id','previous_state','new_state','actor_type','actor_id','reason','decided_at'];
 protected $casts=['decided_at'=>'datetime'];
 public function assertion(): BelongsTo { return $this->belongsTo(Assertion::class); }
}