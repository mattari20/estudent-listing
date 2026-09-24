<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Evidence extends Model
{
    protected $fillable = ['source_id','snapshot_id','reference','excerpt','content_hash','observed_at','published_at','expires_at','status'];
    protected $casts = ['observed_at'=>'datetime','published_at'=>'datetime','expires_at'=>'datetime'];
    public function snapshot(): BelongsTo { return $this->belongsTo(SourceSnapshot::class); }
    public function source(): BelongsTo { return $this->belongsTo(Source::class); }
    public function assertions(): HasMany { return $this->hasMany(Assertion::class); }
}
