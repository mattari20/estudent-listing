<?php
namespace App\Http\Controllers;
use App\Models\AdmissionIntake;
use App\Models\Geography;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use Illuminate\Http\Request;
use Illuminate\View\View;
class InstituteSearchController {
 public function __invoke(Request $request): View {
  $q=trim((string)$request->query('q','')); $type=trim((string)$request->query('type','')); $province=trim((string)$request->query('province','')); $district=trim((string)$request->query('district','')); $city=trim((string)$request->query('city','')); $program=trim((string)$request->query('program','')); $programLevel=trim((string)$request->query('program_level','')); $deliveryMode=trim((string)$request->query('delivery_mode','')); $admissionStatus=trim((string)$request->query('admission_status',''));
  $institutions=Institution::query()->where('status','active')->when($q!=='',function($query)use($q){$query->where(function($search)use($q){$search->where('name','like',"%{$q}%")->orWhere('official_name','like',"%{$q}%")->orWhere('short_name','like',"%{$q}%")->orWhereHas('names',fn($n)=>$n->where('name','like',"%{$q}%"))->orWhereHas('programs',fn($p)=>$p->where('status','active')->where(fn($x)=>$x->where('name','like',"%{$q}%")->orWhere('discipline','like',"%{$q}%")->orWhere('program_level','like',"%{$q}%")))->orWhereHas('campuses.geography',fn($g)=>$g->where('name','like',"%{$q}%"));});})
  ->when($type!=='',fn($x)=>$x->where('institution_type',$type))
  ->when($province!=='',fn($x)=>$x->whereHas('campuses.geography.parent.parent',fn($g)=>$g->where('name',$province)->where('type','province')))
  ->when($district!=='',fn($x)=>$x->whereHas('campuses.geography.parent',fn($g)=>$g->where('name',$district)->where('type','district')))
  ->when($city!=='',fn($x)=>$x->whereHas('campuses.geography',fn($g)=>$g->where('name',$city)->where('type','city')))
  ->when($program!=='',fn($x)=>$x->whereHas('programs',fn($p)=>$p->where('status','active')->where(fn($y)=>$y->where('name','like',"%{$program}%")->orWhere('discipline','like',"%{$program}%"))))
  ->when($programLevel!=='',fn($x)=>$x->whereHas('programs',fn($p)=>$p->where('status','active')->where('program_level',$programLevel)))
  ->when($deliveryMode!=='',fn($x)=>$x->whereHas('programs.offerings',fn($o)=>$o->where('status','active')->where('delivery_mode',$deliveryMode)))
  ->when($admissionStatus!=='',fn($x)=>$x->whereHas('programs.offerings.admissionIntakes',fn($i)=>$i->where('status',$admissionStatus)))
  ->withCount(['campuses','programs'])->orderBy('name')->paginate(24)->withQueryString();
  $types=Institution::where('status','active')->whereNotNull('institution_type')->distinct()->orderBy('institution_type')->pluck('institution_type');
  $provinces=Geography::where('status','active')->where('type','province')->orderBy('name')->pluck('name');
  $districts=Geography::where('status','active')->where('type','district')->orderBy('name')->pluck('name');
  $cities=Geography::where('status','active')->where('type','city')->orderBy('name')->pluck('name');
  $programLevels=Program::where('status','active')->whereNotNull('program_level')->distinct()->orderBy('program_level')->pluck('program_level');
  $deliveryModes=ProgramOffering::where('status','active')->whereNotNull('delivery_mode')->distinct()->orderBy('delivery_mode')->pluck('delivery_mode');
  $admissionStatuses=AdmissionIntake::whereNotNull('status')->distinct()->orderBy('status')->pluck('status');
  $query=$q;
  return view('institutes.search',compact('institutions','query','type','province','district','city','program','programLevel','deliveryMode','admissionStatus','types','provinces','districts','cities','programLevels','deliveryModes','admissionStatuses'));
 }
}