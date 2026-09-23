<?php
use App\Http\Controllers\InstitutionController;
use Illuminate\Support\Facades\Route;
Route::prefix('institutes')->name('institutes.')->group(function (): void {
    Route::get('/', [InstitutionController::class, 'index'])->name('index');
    Route::get('/{institution:slug}', [InstitutionController::class, 'show'])->name('show');
});
