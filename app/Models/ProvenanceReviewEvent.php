<?php
namespace App\Models;

use App\Models\Concerns\PreventsAuditMutation;
use Illuminate\Database\Eloquent\Model;
class ProvenanceReviewEvent extends Model {
    use PreventsAuditMutation;
 protected $fillable=['subject_type','subject_id','event_type','actor_type','actor_id','status','reason','metadata','occurred_at'];
 protected $casts=['metadata'=>'array','occurred_at'=>'datetime'];
}