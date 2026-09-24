<?php
namespace App\Services;
class InstituteSearchProgramLevelService { public function apply($query,string $level){ $level=trim($level); return $level===''?$query:$query->whereHas('programs',fn($p)=>$p->where('status','active')->where('program_level',$level)); } }