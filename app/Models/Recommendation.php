<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Recommendation extends Model {
 protected $fillable=['institution_id','campus_id','program_id','contributor_type','recommendation','rating','experience_period','verification_state','status','comment','notes'];
 protected $casts=['rating'=>'integer'];
 public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
 public function campus(): BelongsTo { return $this->belongsTo(Campus::class); }
 public function program(): BelongsTo { return $this->belongsTo(Program::class); }
}