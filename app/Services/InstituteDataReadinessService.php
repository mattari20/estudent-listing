<?php
namespace App\Services;
use App\Models\Institution;
class InstituteDataReadinessService {
 public function summarize(): array {
  return [
   'institutions'=>Institution::count(),
   'active_institutions'=>Institution::where('status','active')->count(),
   'inactive_institutions'=>Institution::where('status','!=','active')->count(),
  ];
 }
}