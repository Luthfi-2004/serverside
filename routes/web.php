<?php

use Illuminate\Support\Facades\Route;
// routes/web.php
use App\Http\Controllers\ServersideController;

Route::view('/', 'serverside.dashboard')->name('dashboard');
Route::view('/serverside', 'serverside.serverside')->name('serverside.index');

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
