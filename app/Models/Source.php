<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Source extends Model
{
    protected $fillable = ['source_type','name','url','authority_scope','status'];
    public function evidences(): HasMany { return $this->hasMany(Evidence::class); }
}
