<?php
use App\Http\Controllers\InstituteAuthController;
use App\Http\Controllers\InstituteBulkPromotionController;
use App\Http\Controllers\InstituteImportController;
use App\Http\Controllers\InstituteImportPromotionController;
use App\Http\Controllers\InstituteOperationsController;
use App\Http\Controllers\InstitutionController;
use Illuminate\Support\Facades\Route;
Route::prefix('institutes')->name('institutes.')->group(function (): void {
 Route::get('/', [InstitutionController::class, 'index'])->name('index');
 Route::get('/search', \App\Http\Controllers\InstituteSearchController::class)->name('search');
 Route::get('/login', [InstituteAuthController::class,'create'])->name('login');
 Route::post('/login', [InstituteAuthController::class,'store'])->name('login.store');
 Route::post('/logout', [InstituteAuthController::class,'destroy'])->middleware('auth')->name('logout');
 Route::middleware(['auth','institute.operator'])->prefix('operations')->name('operations.')->group(function (): void {
  Route::get('/', [InstituteOperationsController::class,'queue'])->name('queue');
  Route::get('/rows/{importRow}', [InstituteOperationsController::class,'show'])->name('row');
  Route::post('/rows/{importRow}/resolve', [InstituteImportPromotionController::class,'resolve'])->name('resolve');
  Route::post('/rows/{importRow}/decision', [InstituteImportPromotionController::class,'decide'])->name('decide');
  Route::post('/rows/{importRow}/enrichment-decision', [InstituteImportPromotionController::class,'enrichmentDecision'])->name('enrichment-decision');
  Route::post('/rows/{importRow}/promote', [InstituteImportPromotionController::class,'promote'])->name('promote');
  Route::post('/rows/{importRow}/enrichment-promote', [InstituteImportPromotionController::class,'enrichmentPromote'])->name('enrichment-promote');
  Route::post('/rows/{importRow}/reverse', [InstituteImportPromotionController::class,'reverse'])->name('reverse');
  Route::post('/bulk-promote', [InstituteBulkPromotionController::class,'store'])->name('bulk-promote');
  Route::get('/import', [InstituteImportController::class,'create'])->name('import.create');
  Route::get('/import/template', [InstituteImportController::class,'template'])->name('import.template');
  Route::post('/import', [InstituteImportController::class,'store'])->name('import.store');
 });
 Route::get('/{institution:slug}', [InstitutionController::class, 'show'])->name('show');
});