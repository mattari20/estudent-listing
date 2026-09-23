<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    protected $fillable = [
        'institution_id','campus_id','program_id','reviewer_type','target_type',
        'title','body','overall_rating','experience_period','status',
        'verification_state','institution_response_allowed','institution_response',
        'published_at','moderated_at',
    ];

    protected function casts(): array
    {
        return [
            'institution_response_allowed' => 'boolean',
            'published_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    public function institution(): BelongsTo { return $this->belongsTo(Institution::class); }
    public function campus(): BelongsTo { return $this->belongsTo(Campus::class); }
    public function program(): BelongsTo { return $this->belongsTo(Program::class); }
    public function ratings(): HasMany { return $this->hasMany(ReviewRating::class); }
}
