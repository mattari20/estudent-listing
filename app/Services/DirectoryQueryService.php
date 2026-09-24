<?php
namespace App\Services;
use App\Models\Institution;
class DirectoryQueryService {
 public function activeInstitutions(){return Institution::query()->where('status','active')->orderBy('name');}
}