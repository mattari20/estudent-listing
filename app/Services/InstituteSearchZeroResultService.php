<?php
namespace App\Services;
class InstituteSearchZeroResultService { public function suggestions(array $filters): array { $keys=array_keys(array_filter($filters,fn($v)=>is_string($v)&&$v!=='')); return ['active_filter_count'=>count($keys),'recovery_actions'=>['broaden_location','remove_optional_filter','shorten_query']]; } }