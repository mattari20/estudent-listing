<?php
namespace App\Services;
use App\Models\ImportRow;
use Illuminate\Support\Str;
class InstituteImportControllerSupport {
 public function normalize(array $payload): array {
  foreach($payload as $k=>$v){ if(is_string($v)) $payload[$k]=trim(preg_replace('/\\s+/u',' ',$v)); }
  $payload['name']=trim((string)($payload['name']??''));
  foreach(['official_name','short_name','institution_type','status'] as $field) if(array_key_exists($field,$payload) && $payload[$field]!==null) $payload[$field]=trim((string)$payload[$field]);
  if(empty($payload['slug'])) $payload['slug']=Str::slug($payload['name']); else $payload['slug']=Str::slug((string)$payload['slug']);
  return $payload;
 }
 public function validateRow(array $payload,array $seenSlugs): array {
  $errors=[]; if(($payload['name']??'')==='') $errors[]='name is required';
  $slug=(string)($payload['slug']??''); if($slug!=='' && isset($seenSlugs[$slug])) $errors[]='duplicate slug within import batch';
  return $errors;
 }
 public function insertRows(array $rows,int $chunkSize=500): void { foreach(array_chunk($rows,$chunkSize) as $chunk) ImportRow::insert($chunk); }
 public function insertRowsInChunks(array $rows,int $chunkSize=500): void { $this->insertRows($rows,$chunkSize); }
}