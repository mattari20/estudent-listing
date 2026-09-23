<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Accreditation extends Model
{
    protected $fillable = [
        'institution_id','program_id','program_offering_id','campus_id',
        'name','recognition_type','authority_name','scope_type',
        'reference_identifier','status','effective_from','effective_until',
        'last_verified_at','notes',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
            'last_verified_at' => 'date',
        ];
    }

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function program(): BelongsTo { return $this->belongsTo(Program::class); }
    public function programOffering(): BelongsTo { return $this->belongsTo(ProgramOffering::class); }
    public function campus(): BelongsTo { return $this->belongsTo(Campus::class); }
}
