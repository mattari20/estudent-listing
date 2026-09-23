<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProgramOffering extends Model
{
    protected $fillable = ['program_id','campus_id','delivery_mode','status','intake_label','duration','availability','effective_from','effective_until'];
    protected $casts = ['effective_from'=>'date','effective_until'=>'date'];
    public function program(): BelongsTo { return $this->belongsTo(Program::class); }
    public function campus(): BelongsTo { return $this->belongsTo(Campus::class); }
}
