<?php
namespace App\Services;
class InstituteImportPreflightService {
 public function validate(array $rows,array $required=['name']): array {
  $errors=[];$valid=0;
  foreach($rows as $index=>$row){$missing=[];foreach($required as $field){if(!isset($row[$field])||trim((string)$row[$field])==='')$missing[]=$field;}if($missing)$errors[$index]=$missing;else $valid++;}
  return ['valid'=>$valid,'invalid'=>count($errors),'errors'=>$errors];
 }
}