<?php
namespace App\Services;
use App\Models\ImportRow;
use Illuminate\Support\Str;
class InstituteImportControllerSupport {
 public function normalize(array $payload): array {
  foreach($payload as $k=>$v) $payload[$k]=is_string($v)?trim($v):$v;
  $payload['name']=trim((string)($payload['name']??''));
  if(empty($payload['slug'])) $payload['slug']=Str::slug($payload['name']);
  return $payload;
 }
 public function validateRow(array $payload,array $seenSlugs): array {
  $errors=[]; if(($payload['name']??'')==='') $errors[]='name is required';
  $slug=(string)($payload['slug']??'');
  if($slug!=='' && isset($seenSlugs[$slug])) $errors[]='duplicate slug within import batch';
  return $errors;
 }
 public function insertRowsInChunks(array $rows,int $chunkSize=500): void {
  foreach(array_chunk($rows,$chunkSize) as $chunk) ImportRow::insert($chunk);
 }
}