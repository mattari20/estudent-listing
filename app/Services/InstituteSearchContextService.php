<?php
namespace App\Services;
class InstituteSearchContextService { public function detect(string $q): array { $q=trim($q); return ['query'=>$q,'has_query'=>$q!=='']; } }