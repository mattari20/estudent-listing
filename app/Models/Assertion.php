<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Assertion extends Model
{
    protected $fillable = ['evidence_id','subject_type','subject_id','attribute','value','verification_state','confidence','valid_from','valid_until','verified_at'];
    protected $casts = ['confidence'=>'decimal:4','valid_from'=>'datetime','valid_until'=>'datetime','verified_at'=>'datetime'];
    public function evidence(): BelongsTo { return $this->belongsTo(Evidence::class); }
}
