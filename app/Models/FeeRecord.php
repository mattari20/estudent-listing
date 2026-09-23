<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FeeRecord extends Model
{
    protected $fillable = ['program_offering_id','admission_intake_id','fee_type','amount_type','amount','minimum_amount','maximum_amount','currency','frequency','status','effective_from','effective_until','notes'];
    protected $casts = ['amount'=>'decimal:2','minimum_amount'=>'decimal:2','maximum_amount'=>'decimal:2','effective_from'=>'date','effective_until'=>'date'];
    public function offering(): BelongsTo { return $this->belongsTo(ProgramOffering::class,'program_offering_id'); }
    public function admissionIntake(): BelongsTo { return $this->belongsTo(AdmissionIntake::class); }
}
