<?php
namespace App\Services;
use App\Models\Institution;
class InstituteSearchQueryService {
 public function build(string $q) {
  return Institution::query()->where('status','active')->when($q!=='',function($query)use($q){$query->where(function($s)use($q){
   $s->where('name','like',"%{$q}%")->orWhere('official_name','like',"%{$q}%")->orWhere('short_name','like',"%{$q}%")
   ->orWhereHas('names',fn($n)=>$n->where('name','like',"%{$q}%"))
   ->orWhereHas('programs',fn($p)=>$p->where('status','active')->where(fn($x)=>$x->where('name','like',"%{$q}%")->orWhere('discipline','like',"%{$q}%")->orWhere('program_level','like',"%{$q}%")))
   ->orWhereHas('campuses',fn($c)=>$c->where('status','active')->where('name','like',"%{$q}%"))
   ->orWhereHas('campuses.geography',fn($g)=>$g->where('status','active')->where('name','like',"%{$q}%"));
  });});
 }
}