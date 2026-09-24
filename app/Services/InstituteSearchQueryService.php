<?php
namespace App\Services;
use App\Models\Institution;
class InstituteSearchQueryService { public function build(string $q){ return Institution::query()->where('status','active')->when($q!=='',fn($query)=>$query->where(fn($s)=>$s->where('name','like',"%{$q}%")->orWhere('official_name','like',"%{$q}%")->orWhere('short_name','like',"%{$q}%")->orWhereHas('names',fn($n)=>$n->where('name','like',"%{$q}%"))); } }