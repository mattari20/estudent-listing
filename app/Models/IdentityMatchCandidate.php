<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class IdentityMatchCandidate extends Model {
 protected $fillable=['import_row_id','institution_id','match_type','confidence','status','reason','matched_at'];
 protected $casts=['confidence'=>'decimal:4','matched_at'=>'datetime'];
 public function importRow(): BelongsTo { return $this->belongsTo(ImportRow::class); }
 public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
}