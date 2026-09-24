<?php
namespace App\Services;
class InstituteSearchAdmissionService { public function apply($query,string $status){ $status=trim($status); return $status===''?$query:$query->whereHas('programs.offerings.admissionIntakes',fn($i)=>$i->where('status',$status)); } }