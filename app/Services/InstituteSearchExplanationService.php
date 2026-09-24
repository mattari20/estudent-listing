<?php
namespace App\Services;
class InstituteSearchExplanationService { public function explain(array $filters): array { $items=[]; foreach($filters as $key=>$value){ if(is_string($value)&&$value!=='')$items[]=['filter'=>$key,'value'=>$value]; } return $items; } }