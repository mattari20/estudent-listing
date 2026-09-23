<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ChangeEvent extends Model
{
    protected $fillable = ['subject_type','subject_id','event_type','materiality','actor_type','status','summary','detected_at'];
    protected $casts = ['detected_at'=>'datetime'];
}
