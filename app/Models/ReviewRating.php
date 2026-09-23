<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewRating extends Model
{
    protected $fillable = ['review_id','dimension','rating','notes'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
