<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SecurityAuditEvent extends Model {
 protected $fillable=['event_type','actor_type','actor_id','subject_type','subject_id','severity','description','request_id'];
}