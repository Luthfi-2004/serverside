<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServersideController;
use App\Http\Controllers\GreensandController;

Route::get('/greensand/export', [GreensandController::class, 'export'])->name('greensand.export');
Route::view('/', 'serverside.dashboard')->name('dashboard');
Route::view('/serverside', 'serverside.greensand')->name('serverside.index');
Route::get('/serverside/summary', [ServersideController::class, 'summaryAll'])->name('serverside.summary');

Route::prefix('serverside')->name('serverside.')->group(function () {
    Route::get('/data/mm1', [ServersideController::class, 'dataMM1'])->name('data.mm1');
    Route::get('/data/mm2', [ServersideController::class, 'dataMM2'])->name('data.mm2');
    Route::get('/data/all', [ServersideController::class, 'dataAll'])->name('data.all');

    // CRUD JSON endpoints
    Route::post('/processes', [ServersideController::class, 'store'])->name('processes.store');
    Route::get('/processes/{id}', [ServersideController::class, 'show'])->name('processes.show');
    Route::put('/processes/{id}', [ServersideController::class, 'update'])->name('processes.update');
    Route::delete('/processes/{id}', [ServersideController::class, 'destroy'])->name('processes.destroy');
});
