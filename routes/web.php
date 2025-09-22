<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GreensandJshController;
use App\Http\Controllers\GreensandController;
use App\Http\Controllers\JshGfnPageController;
use App\Http\Controllers\AceLineController;

/* --- JSH GFN (punya kamu) --- */
Route::prefix('jsh-gfn')->name('jshgfn.')->group(function () {
    Route::get('/', [JshGfnPageController::class, 'index'])->name('index');
    Route::post('/', [JshGfnPageController::class, 'store'])->name('store');
    Route::post('/delete-today', [JshGfnPageController::class, 'deleteTodaySet'])->name('deleteToday');
    Route::get('/export', [JshGfnPageController::class, 'export'])->name('export');
});

/* --- Dashboard & JSH LINE Daily Check (punya kamu) --- */
Route::view('/', 'greensand.dashboard')->name('dashboard');
Route::view('/greensand', 'greensand.greensand')->name('greensand.index');
Route::get('/greensand/export', [GreensandJshController::class, 'export'])->name('greensand.export');
Route::get('/greensand/summary', [GreensandJshController::class, 'summaryAll'])->name('greensand.summary');

Route::prefix('greensand')->name('greensand.')->group(function () {
    Route::get('/data/mm1', [GreensandJshController::class, 'dataMM1'])->name('data.mm1');
    Route::get('/data/mm2', [GreensandJshController::class, 'dataMM2'])->name('data.mm2');
    Route::get('/data/all', [GreensandJshController::class, 'dataAll'])->name('data.all');
    Route::post('/processes', [GreensandJshController::class, 'store'])->name('processes.store');
    Route::get('/processes/{id}', [GreensandJshController::class, 'show'])->name('processes.show');
    Route::put('/processes/{id}', [GreensandJshController::class, 'update'])->name('processes.update');
    Route::delete('/processes/{id}', [GreensandJshController::class, 'destroy'])->name('processes.destroy');
});

/* --- ACE LINE --- */
/* Halaman utama (Daily Check) – saat ini direct ke Blade, nanti gampang diganti ke controller index */
Route::view('/ace', 'ace.index')->name('ace.index');

Route::prefix('ace')->name('ace.')->group(function () {
    Route::get('/data',   [AceLineController::class, 'data'])->name('data');   // <— INI YANG DIBUTUHKAN
    Route::post('/',      [AceLineController::class, 'store'])->name('store');
    Route::get('/{id}',   [AceLineController::class, 'show'])->name('show');
    Route::put('/{id}',   [AceLineController::class, 'update'])->name('update');
    Route::delete('/{id}',[AceLineController::class, 'destroy'])->name('destroy');

    Route::get('/export',  [AceLineController::class, 'export'])->name('export');
    Route::get('/summary', [AceLineController::class, 'summary'])->name('summary');
});
