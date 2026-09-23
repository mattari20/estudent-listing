<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EligibilityRule extends Model
{
    protected $fillable = ['admission_intake_id','rule_type','applicant_category','qualification','requirement','minimum_marks','minimum_marks_type','required_subject','entry_test_required','domicile_requirement','minimum_age','maximum_age','experience_requirement','status'];
    protected $casts = ['minimum_marks'=>'decimal:2','entry_test_required'=>'boolean'];
    public function admissionIntake(): BelongsTo { return $this->belongsTo(AdmissionIntake::class); }
}
