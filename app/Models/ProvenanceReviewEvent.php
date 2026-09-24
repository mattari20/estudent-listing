<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProvenanceReviewEvent extends Model {
 protected $fillable=['subject_type','subject_id','event_type','actor_type','actor_id','status','reason','metadata','occurred_at'];
 protected $casts=['metadata'=>'array','occurred_at'=>'datetime'];
}