<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class AdmissionIntake extends Model
{
    protected $fillable = ['program_offering_id','label','status','application_open_at','application_close_at','test_date','selection_date','enrollment_start_at','enrollment_end_at','seat_capacity','application_url','notes'];
    protected $casts = ['application_open_at'=>'date','application_close_at'=>'date','test_date'=>'date','selection_date'=>'date','enrollment_start_at'=>'date','enrollment_end_at'=>'date'];
    public function offering(): BelongsTo { return $this->belongsTo(ProgramOffering::class,'program_offering_id'); }
    public function eligibilityRules(): HasMany { return $this->hasMany(EligibilityRule::class); }
    public function meritRecords(): HasMany { return $this->hasMany(MeritRecord::class); }
    public function selectionOutcomes(): HasMany { return $this->hasMany(SelectionOutcome::class); }
}
