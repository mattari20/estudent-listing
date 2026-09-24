<?php
namespace App\Services;
class InstituteSearchRelevanceService {
 public function score($institution,string $q): int { if($q==='') return 0; $q=mb_strtolower(trim($q)); $name=mb_strtolower((string)$institution->name); $official=mb_strtolower((string)$institution->official_name); $short=mb_strtolower((string)$institution->short_name); if($name===$q||$official===$q||$short===$q)return 100; if(str_starts_with($name,$q)||str_starts_with($official,$q)||str_starts_with($short,$q))return 80; if(str_contains($name,$q)||str_contains($official,$q)||str_contains($short,$q))return 60; return 0; }
}