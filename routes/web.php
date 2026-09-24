<?php

use App\Http\Controllers\GeographyImportController;
use App\Http\Controllers\InstituteAuthController;
use App\Http\Controllers\InstituteBulkPromotionController;
use App\Http\Controllers\InstituteImportController;
use App\Http\Controllers\InstituteImportPromotionController;
use App\Http\Controllers\InstituteOperationsController;
use App\Http\Controllers\InstituteRobotsController;
use App\Http\Controllers\InstituteSearchController;
use App\Http\Controllers\InstituteSitemapController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\MonitoringExportController;
use App\Http\Controllers\MonitoringOperationsController;
use App\Http\Controllers\MonitoringPolicyController;
use Illuminate\Support\Facades\Route;

Route::prefix('institutes')->name('institutes.')->group(function (): void {
    Route::get('/', [InstitutionController::class, 'index'])->name('index');
    Route::get('/search', InstituteSearchController::class)->name('search');
    Route::get('/sitemap.xml', InstituteSitemapController::class)->name('sitemap');
    Route::get('/robots.txt', InstituteRobotsController::class)->name('robots');
    Route::get('/login', [InstituteAuthController::class, 'create'])->name('login');
    Route::post('/login', [InstituteAuthController::class, 'store'])->name('login.store');
    Route::post('/logout', [InstituteAuthController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'institute.operator'])->prefix('operations')->name('operations.')->group(function (): void {
        Route::get('/', [InstituteOperationsController::class, 'queue'])->name('queue');
        Route::get('/rows/{importRow}', [InstituteOperationsController::class, 'show'])->name('row');
        Route::post('/rows/{importRow}/resolve', [InstituteImportPromotionController::class, 'resolve'])->name('resolve');
        Route::post('/rows/{importRow}/decision', [InstituteImportPromotionController::class, 'decide'])->name('decide');
        Route::post('/rows/{importRow}/conflict-resolve', [InstituteImportPromotionController::class, 'resolveConflict'])->name('conflict-resolve');
        Route::post('/rows/{importRow}/enrichment-decision', [InstituteImportPromotionController::class, 'enrichmentDecision'])->name('enrichment-decision');
        Route::post('/rows/{importRow}/geography-resolve', [InstituteImportPromotionController::class, 'geographyResolve'])->name('geography-resolve');
        Route::post('/rows/{importRow}/geography-promote', [InstituteImportPromotionController::class, 'geographyPromote'])->name('geography-promote');
        Route::post('/rows/{importRow}/promote', [InstituteImportPromotionController::class, 'promote'])->name('promote');
        Route::post('/rows/{importRow}/enrichment-promote', [InstituteImportPromotionController::class, 'enrichmentPromote'])->name('enrichment-promote');
        Route::post('/rows/{importRow}/reverse', [InstituteImportPromotionController::class, 'reverse'])->name('reverse');
        Route::post('/reverification/{item}/start', [InstituteImportPromotionController::class, 'startReverification'])->name('reverification.start');
        Route::post('/reverification/{item}/resolve', [InstituteImportPromotionController::class, 'resolveReverification'])->name('reverification.resolve');
        Route::post('/reverification/{item}/refresh', [InstituteImportPromotionController::class, 'refreshCanonical'])->name('reverification.refresh');
        Route::post('/bulk-promote', [InstituteBulkPromotionController::class, 'store'])->name('bulk-promote');
        Route::get('/monitoring', [MonitoringOperationsController::class, 'dashboard'])->name('monitoring.dashboard');
        Route::get('/monitoring/policies/create', [MonitoringPolicyController::class, 'create'])->name('monitoring.policies.create');
        Route::post('/monitoring/policies', [MonitoringPolicyController::class, 'store'])->name('monitoring.policies.store');
        Route::post('/monitoring/policies/{policy}/toggle', [MonitoringPolicyController::class, 'toggle'])->name('monitoring.policies.toggle');
        Route::get('/monitoring/export/alerts', [MonitoringExportController::class, 'alerts'])->name('monitoring.export.alerts');
        Route::get('/monitoring/export/changes', [MonitoringExportController::class, 'changes'])->name('monitoring.export.changes');
        Route::get('/monitoring/history', [MonitoringOperationsController::class, 'history'])->name('monitoring.history');
        Route::post('/monitoring/alerts/{alert}/acknowledge', [MonitoringOperationsController::class, 'acknowledge'])->name('monitoring.alerts.acknowledge');
        Route::post('/monitoring/alerts/{alert}/resolve', [MonitoringOperationsController::class, 'resolve'])->name('monitoring.alerts.resolve');
        Route::get('/geography-import', [GeographyImportController::class, 'create'])->name('geography-import.create');
        Route::get('/geography-import/template', [GeographyImportController::class, 'template'])->name('geography-import.template');
        Route::post('/geography-import', [GeographyImportController::class, 'store'])->name('geography-import.store');
        Route::get('/import', [InstituteImportController::class, 'create'])->name('import.create');
        Route::get('/import/template', [InstituteImportController::class, 'template'])->name('import.template');
        Route::post('/import', [InstituteImportController::class, 'store'])->name('import.store');
    });

    Route::get('/{institution:slug}', [InstitutionController::class, 'show'])->name('show');
});
