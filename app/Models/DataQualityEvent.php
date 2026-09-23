<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DataQualityEvent extends Model {
 protected $fillable=['subject_type','subject_id','dimension','state','severity','description','observed_at','resolved_at'];
 protected $casts=['observed_at'=>'datetime','resolved_at'=>'datetime'];
}