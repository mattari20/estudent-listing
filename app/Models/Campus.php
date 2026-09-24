<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campus extends Model
{
    protected $fillable = [
        'institution_id',
        'geography_id',
        'name',
        'slug',
        'campus_type',
        'status',
        'address',
        'postal_code',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function geography(): BelongsTo
    {
        return $this->belongsTo(Geography::class);
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(ProgramOffering::class);
    }
}
