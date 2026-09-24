<?php
namespace App\Services;
class InstituteSearchCampusService { public function apply($query,string $term){ $term=trim($term); if($term==='')return $query; return $query->orWhereHas('campuses',fn($c)=>$c->where('status','active')->where('name','like',"%{$term}%")); } }