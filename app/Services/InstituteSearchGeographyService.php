<?php
namespace App\Services;
class InstituteSearchGeographyService { public function apply($query,string $term){ $term=trim($term); if($term==='')return $query; return $query->orWhereHas('campuses.geography',fn($g)=>$g->where('status','active')->where('name','like',"%{$term}%")); } }