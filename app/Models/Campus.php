<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Campus extends Model
{
    protected $fillable = ['institution_id','geography_id','name','slug','campus_type','status','address','postal_code','latitude','longitude'];
    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function geography(): BelongsTo { return $this->belongsTo(Geography::class); }
}
