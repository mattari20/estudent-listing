<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RankingMethodology extends Model {
 protected $fillable=['name','version','status','scope','methodology_text','effective_from','effective_until'];
 protected $casts=['effective_from'=>'date','effective_until'=>'date'];
}