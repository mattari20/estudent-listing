<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SelectionOutcome extends Model
{
    protected $fillable = ['admission_intake_id','outcome_type','applicant_category','status','threshold_value','threshold_score','unit','list_number','notes','effective_date'];
    protected $casts = ['threshold_score'=>'decimal:4','effective_date'=>'date'];
    public function admissionIntake(): BelongsTo { return $this->belongsTo(AdmissionIntake::class); }
}
