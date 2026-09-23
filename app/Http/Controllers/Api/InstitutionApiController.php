<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class InstitutionApiController extends Controller {
 public function index(Request $request): JsonResponse {
  $q=trim((string)$request->query('q',''));
  $items=Institution::query()->where('status','active')->when($q!=='',fn($x)=>$x->where(function($y)use($q){$y->where('name','like',"%$q%")->orWhere('official_name','like',"%$q%")->orWhere('short_name','like',"%$q%");}))->orderBy('name')->paginate(min((int)$request->query('per_page',24),100));
  return response()->json($items);
 }
 public function show(Institution $institution): JsonResponse {
  return response()->json($institution->load(['campuses','programs']));
 }
}