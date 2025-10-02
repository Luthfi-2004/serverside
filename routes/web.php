<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\GreensandJshController;
use App\Http\Controllers\GreensandController;
use App\Http\Controllers\JshGfnPageController;
use App\Http\Controllers\AceLineController;
use App\Http\Controllers\AceGfnPageController;
use App\Http\Controllers\AceStandardController;
use App\Http\Controllers\AceSummaryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Note:
| - Tidak pakai middleware auth (sementara).
| - ACE Summary: GET /ace/summary  -> name: ace.summary
| - ACE Standards (UI 1 form Save): GET/POST /ace/standards
*/

/* --- Dashboard & JSH LINE Daily Check --- */
Route::view('/', 'greensand.dashboard')->name('dashboard');
Route::view('/greensand', 'greensand.greensand')->name('greensand.index');
Route::get('/greensand/export', [GreensandJshController::class, 'export'])->name('greensand.export');
Route::get('/greensand/summary', [GreensandJshController::class, 'summaryAll'])->name('greensand.summary');

Route::prefix('greensand')->name('greensand.')->group(function () {
    Route::get('/data/mm1', [GreensandJshController::class, 'dataMM1'])->name('data.mm1');
    Route::get('/data/mm2', [GreensandJshController::class, 'dataMM2'])->name('data.mm2');
    Route::get('/data/all', [GreensandJshController::class, 'dataAll'])->name('data.all');

    Route::post('/processes', [GreensandJshController::class, 'store'])->name('processes.store');
    Route::get('/processes/{id}', [GreensandJshController::class, 'show'])->whereNumber('id')->name('processes.show');
    Route::put('/processes/{id}', [GreensandJshController::class, 'update'])->whereNumber('id')->name('processes.update');
    Route::delete('/processes/{id}', [GreensandJshController::class, 'destroy'])->whereNumber('id')->name('processes.destroy');
});

/* --- JSH GFN --- */
Route::prefix('jsh-gfn')->name('jshgfn.')->group(function () {
    Route::get('/', [JshGfnPageController::class, 'index'])->name('index');
    Route::post('/', [JshGfnPageController::class, 'store'])->name('store');
    Route::post('/delete-today', [JshGfnPageController::class, 'deleteTodaySet'])->name('deleteToday');
    Route::get('/export', [JshGfnPageController::class, 'export'])->name('export');
    Route::get('/check-exists', [JshGfnPageController::class, 'checkExists'])->name('check-exists');
});

/* --- ACE LINE (Daily Check, Summary, Standards) --- */
Route::view('/ace', 'ace.index')->name('ace.index');

Route::prefix('ace')->name('ace.')->group(function () {
    // Data table + export
    Route::get('/data',   [AceLineController::class, 'data'])->name('data');
    Route::get('/export', [AceLineController::class, 'export'])->name('export');

    // Summary (dipakai footer MIN/MAX/AVG/JUDGE di ace.js)
    Route::get('/summary', AceSummaryController::class)->name('summary');

    // CRUD record harian
    Route::post('/',      [AceLineController::class, 'store'])->name('store');
    Route::get('/{id}',   [AceLineController::class, 'show'])->whereNumber('id')->name('show');
    Route::put('/{id}',   [AceLineController::class, 'update'])->whereNumber('id')->name('update');
    Route::delete('/{id}',[AceLineController::class, 'destroy'])->whereNumber('id')->name('destroy');

    // Standards (UI simple: satu form, satu tombol Save)
    Route::get('/standards',  [AceStandardController::class, 'index'])->name('standards');
    Route::post('/standards', [AceStandardController::class, 'update'])->name('standards.update');
});

/* --- GFN ACE LINE --- */
Route::prefix('aceline-gfn')->name('acelinegfn.')->group(function () {
    Route::get('/', [AceGfnPageController::class, 'index'])->name('index');
    Route::post('/store', [AceGfnPageController::class, 'store'])->name('store');
    Route::post('/delete-today', [AceGfnPageController::class, 'deleteTodaySet'])->name('deleteToday');
    Route::get('/export', [AceGfnPageController::class, 'export'])->name('export');
    Route::get('/check-exists', [AceGfnPageController::class, 'checkExists'])->name('check-exists');
});
