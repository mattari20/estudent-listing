<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class FeeRecord extends Model
{
    protected $fillable = [
        'institution_id','program_id','program_offering_id','campus_id',
        'admission_intake_id','fee_type','data_state','amount_min','amount_max',
        'currency','unit','period','payment_stage','applicant_category',
        'delivery_mode','effective_from','effective_until','notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_min' => 'decimal:2',
            'amount_max' => 'decimal:2',
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function program(): BelongsTo { return $this->belongsTo(Program::class); }
    public function programOffering(): BelongsTo { return $this->belongsTo(ProgramOffering::class); }
    public function campus(): BelongsTo { return $this->belongsTo(Campus::class); }
    public function admissionIntake(): BelongsTo { return $this->belongsTo(AdmissionIntake::class); }
}
