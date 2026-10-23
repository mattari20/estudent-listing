<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ImportBatch extends Model {
 protected $fillable=['batch_key','entity_type','source_type','source_id','status','total_rows','valid_rows','invalid_rows','candidate_rows','duplicate_rows','received_at','completed_at','notes'];
 protected $casts=['received_at'=>'datetime','completed_at'=>'datetime'];
 public function source(): BelongsTo { return $this->belongsTo(Source::class); }
 public function rows(): HasMany { return $this->hasMany(ImportRow::class); }
}