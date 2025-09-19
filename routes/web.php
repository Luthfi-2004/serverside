<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GreensandJshController;
use App\Http\Controllers\GreensandController;    
// routes/web.php
use App\Http\Controllers\JshGfnPageController;

// routes/web.php
Route::prefix('jsh-gfn')->name('jshgfn.')->group(function () {
    Route::get('/', [JshGfnPageController::class, 'index'])->name('index');
    Route::post('/', [JshGfnPageController::class, 'store'])->name('store');
    Route::post('/delete-today', [JshGfnPageController::class, 'deleteTodaySet'])->name('deleteToday');
    Route::get('/export', [JshGfnPageController::class, 'export'])->name('export');
});



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
