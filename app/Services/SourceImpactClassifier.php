<?php
namespace App\Services;
class SourceImpactClassifier {
 public function classify(?string $attribute): string {
  $a=strtolower(trim((string)$attribute));
  if(in_array($a,['name','official_name','short_name','status','institution_type','accreditation','recognition','license','program_availability','admission_status','application_deadline','eligibility','merit','fee','tuition','scholarship'],true)) return 'high';
  if(in_array($a,['address','city','district','province','phone','email','website','duration','delivery_mode'],true)) return 'medium';
  if($a==='') return 'unknown';
  return 'low';
 }
}