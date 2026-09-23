<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Program extends Model
{
    protected $fillable = ['institution_id','name','slug','program_level','award','discipline','status','description'];
    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function offerings(): HasMany { return $this->hasMany(ProgramOffering::class); }\n    public function externalRankings(): HasMany { return $this->hasMany(ExternalRanking::class); }
}
