<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ExternalRanking extends Model {
 protected $fillable=['institution_id','program_id','publisher_name','ranking_name','edition','methodology_summary','scope_type','geography_scope','discipline_scope','year','rank_value','rank_label','score','percentile','signal_type','status','effective_from','effective_until','source_url','notes'];
 protected $casts=['year'=>'integer','rank_value'=>'integer','score'=>'decimal:4','percentile'=>'decimal:4','effective_from'=>'date','effective_until'=>'date'];
 public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
 public function program(): BelongsTo { return $this->belongsTo(Program::class); }
}