<?php
namespace App\Services;
class GeographyImportBatchService {
 public function normalize(array $row): array {
  foreach($row as $key=>$value){if(is_string($value))$row[$key]=trim($value);}
  if(isset($row['slug']))$row['slug']=strtolower(trim($row['slug']));
  return $row;
 }
 public function validate(array $row): array {
  $errors=[];
  foreach(['name','slug','type'] as $key)if(($row[$key]??'')==='')$errors[]=$key;
  if(($row['type']??'')!=='country' && (($row['parent_slug']??'')===''||($row['parent_type']??'')===''))$errors[]='parent';
  return $errors;
 }
}