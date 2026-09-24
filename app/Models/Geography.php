<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Geography extends Model
{
    protected $fillable = ['parent_id', 'name', 'slug', 'type', 'code', 'status'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function campuses(): HasMany
    {
        return $this->hasMany(Campus::class);
    }
}
