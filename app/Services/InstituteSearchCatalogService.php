<?php
namespace App\Services;
use App\Models\AdmissionIntake;
use App\Models\Geography;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use Illuminate\Support\Facades\Cache;
class InstituteSearchCatalogService {
 public function filters(): array {
  return Cache::remember('institutes.search.catalog.v1',600,function(){ return [
   'types'=>Institution::where('status','active')->whereNotNull('institution_type')->distinct()->orderBy('institution_type')->pluck('institution_type'),
   'provinces'=>Geography::where('status','active')->where('type','province')->orderBy('name')->pluck('name'),
   'districts'=>Geography::where('status','active')->where('type','district')->orderBy('name')->pluck('name'),
   'cities'=>Geography::where('status','active')->where('type','city')->orderBy('name')->pluck('name'),
   'programLevels'=>Program::where('status','active')->whereNotNull('program_level')->distinct()->orderBy('program_level')->pluck('program_level'),
   'deliveryModes'=>ProgramOffering::where('status','active')->whereNotNull('delivery_mode')->distinct()->orderBy('delivery_mode')->pluck('delivery_mode'),
   'admissionStatuses'=>AdmissionIntake::whereNotNull('status')->whereNotIn('status',['cancelled','suspended'])->distinct()->orderBy('status')->pluck('status'),
  ];});
 }
}