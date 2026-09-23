<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scholarship extends Model
{
    protected $fillable = [
        'institution_id','program_id','program_offering_id','campus_id',
        'admission_intake_id','name','scholarship_type','eligibility_basis',
        'eligibility_description','benefit_type','amount_min','amount_max',
        'currency','benefit_frequency','duration','renewal_condition',
        'application_open','application_close','data_state','status',
        'effective_from','effective_until','notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_min' => 'decimal:2',
            'amount_max' => 'decimal:2',
            'application_open' => 'date',
            'application_close' => 'date',
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
