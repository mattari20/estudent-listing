<?php
namespace App\Services;
use App\Models\Institution;
class InstituteSearchProgramService { public function apply($query,string $term){ $term=trim($term); if($term==='')return $query; return $query->orWhereHas('programs',fn($p)=>$p->where('status','active')->where(fn($x)=>$x->where('name','like',"%{$term}%")->orWhere('discipline','like',"%{$term}%"))); } }