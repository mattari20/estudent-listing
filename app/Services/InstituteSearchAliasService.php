<?php
namespace App\Services;
class InstituteSearchAliasService { public function expand(string $q): array { $q=trim($q); return $q===''?[]:[$q]; } }