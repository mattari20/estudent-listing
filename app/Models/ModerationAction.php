<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ModerationAction extends Model {
 protected $fillable=['target_type','target_id','action','reason','actor_type','actor_id','notes'];
}