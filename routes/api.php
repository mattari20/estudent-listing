<?php
use App\Http\Controllers\Api\InstitutionApiController;
use Illuminate\Support\Facades\Route;
Route::prefix('v1')->group(function (): void {
 Route::get('/institutions/nearby',[InstitutionApiController::class,'nearby']);
 Route::get('/institutions',[InstitutionApiController::class,'index']);
 Route::get('/institutions/{institution:slug}',[InstitutionApiController::class,'show']);
});