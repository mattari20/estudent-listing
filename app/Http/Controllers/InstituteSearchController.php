<?php
namespace App\Http\Controllers;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\View\View;
class InstituteSearchController {
 public function __invoke(Request $request): View {
  $q=trim((string)$request->query('q',''));
  $institutions=Institution::query()->where('status','active')->when($q!=='',fn($query)=>$query->where(function($x)use($q){$x->where('name','like','%'.$q.'%')->orWhere('official_name','like','%'.$q.'%')->orWhere('short_name','like','%'.$q.'%');}))->orderBy('name')->paginate(24)->withQueryString();
  return view('institutes.search',['institutions'=>$institutions,'query'=>$q]);
 }
}