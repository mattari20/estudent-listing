<?php
namespace App\Services;
class InstituteSearchResultService { public function order($institutions,string $q){ if($q==='') return $institutions; $relevance=new InstituteSearchRelevanceService; return $institutions->sortByDesc(fn($i)=>$relevance->score($i,$q))->values(); } }