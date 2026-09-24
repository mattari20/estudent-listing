<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ImportSchemaProfile extends Model {
 protected $fillable=['profile_key','entity_type','version','status','columns','required_columns','normalization_rules','description'];
 protected $casts=['columns'=>'array','required_columns'=>'array','normalization_rules'=>'array'];
}