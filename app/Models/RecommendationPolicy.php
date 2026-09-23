<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RecommendationPolicy extends Model {
 protected $fillable=['name','version','status','description','eligibility_rules','effective_from','effective_until'];
 protected $casts=['effective_from'=>'date','effective_until'=>'date'];
}