<?php
namespace App\Services;
use App\Models\Geography;
class GeographyImportIntegrityService {
 public function validParent(string $type,?Geography $parent): bool {
  if($type==='country')return $parent===null;
  return $parent!==null && $parent->status==='active';
 }
}