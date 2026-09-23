<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class MeritRecord extends Model
{
    protected $fillable = ['admission_intake_id','stage','merit_type','applicant_category','campus_context','value','score','position','unit','methodology','list_label','list_number','status','effective_date'];
    protected $casts = ['score'=>'decimal:4','position'=>'decimal:4','effective_date'=>'date'];
    public function admissionIntake(): BelongsTo { return $this->belongsTo(AdmissionIntake::class); }
}
