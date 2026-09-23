<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class InstitutionName extends Model
{
    protected $fillable = ['institution_id','name','name_type','language','is_current'];
    protected $casts = ['is_current'=>'boolean'];
    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
}
