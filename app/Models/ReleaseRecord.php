<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ReleaseRecord extends Model {
 protected $fillable=['version','environment','status','summary','rollback_notes','released_at'];
 protected $casts=['released_at'=>'datetime'];
}