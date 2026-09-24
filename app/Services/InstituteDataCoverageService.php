<?php
namespace App\Services;
use App\Models\Institution;
class InstituteDataCoverageService {
 public function institutionCoverage(): array {
  $total=Institution::count(); if($total===0)return ['total'=>0,'with_type'=>0,'with_description'=>0,'type_coverage'=>0,'description_coverage'=>0];
  $withType=Institution::whereNotNull('institution_type')->where('institution_type','!=','')->count();
  $withDescription=Institution::whereNotNull('description')->where('description','!=','')->count();
  return ['total'=>$total,'with_type'=>$withType,'with_description'=>$withDescription,'type_coverage'=>round($withType/$total*100,2),'description_coverage'=>round($withDescription/$total*100,2)];
 }
}