<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Institution extends Model
{
    protected $fillable = ['name','official_name','short_name','slug','institution_type','status','description','verified_at'];
    protected $casts = ['verified_at'=>'datetime'];
    public function names(): HasMany { return $this->hasMany(InstitutionName::class); }
    public function campuses(): HasMany { return $this->hasMany(Campus::class); }
    public function programs(): HasMany { return $this->hasMany(Program::class); }
    public function accreditations(): HasMany { return $this->hasMany(Accreditation::class); }\n    public function externalRankings(): HasMany { return $this->hasMany(ExternalRanking::class); }
}
