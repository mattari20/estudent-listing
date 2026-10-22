<?php
namespace App\Services;
class InstituteSearchQueryParser { public function parse(array $input): array { $input['q'] ??= ''; return array_map(fn($v)=>is_string($v)?trim($v):$v,$input); } }