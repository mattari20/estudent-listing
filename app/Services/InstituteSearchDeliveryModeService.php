<?php
namespace App\Services;
class InstituteSearchDeliveryModeService { public function apply($query,string $mode){ $mode=trim($mode); return $mode===''?$query:$query->whereHas('programs.offerings',fn($o)=>$o->where('status','active')->where('delivery_mode',$mode)); } }