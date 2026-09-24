<?php
namespace App\Services;
use App\Models\Institution;
class InstituteSearchGovernedAliasService { public function apply($query,string $term){ $term=trim($term); if($term==='')return $query; return $query->orWhereHas('names',fn($n)=>$n->where('name','like',"%{$term}%")); } }