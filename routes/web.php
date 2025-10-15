<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\GreensandJshController;
use App\Http\Controllers\JshGfnPageController;
use App\Http\Controllers\AceLineController;
use App\Http\Controllers\AceGfnPageController;
use App\Http\Controllers\AceStandardController;
use App\Http\Controllers\AceSummaryController;
use App\Http\Controllers\JshStandardController;
use App\Http\Controllers\GreensandSummaryController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/debug/wip-products', function () {
    try {
        $rows = \Illuminate\Support\Facades\DB::connection('mysql_wip')
            ->table('products')->select(['id', 'no', 'name'])->limit(10)->get();
        return response()->json(['ok' => true, 'count' => $rows->count(), 'rows' => $rows]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'err' => $e->getMessage()], 500);
    }
});

Route::middleware(['auth', \App\Http\Middleware\CheckPermission::class])->group(function () {
    Route::view('/', 'greensand.dashboard')->name('dashboard');

    Route::get('/lookup/products', [AceLineController::class, 'lookupProducts'])->name('lookup.products');

    Route::view('/greensand', 'greensand.greensand')->name('greensand.index');
    Route::get('/greensand/export', [GreensandJshController::class, 'export'])->name('greensand.export');
    Route::get('/greensand/summary', [GreensandSummaryController::class, 'jsh'])->name('greensand.summary');

    Route::prefix('greensand')->name('greensand.')->group(function () {
        Route::get('/data/mm1', [GreensandJshController::class, 'dataMM1'])->name('data.mm1');
        Route::get('/data/mm2', [GreensandJshController::class, 'dataMM2'])->name('data.mm2');
        Route::get('/data/all', [GreensandJshController::class, 'dataAll'])->name('data.all');
        Route::get('/standards', [JshStandardController::class, 'index'])->name('standards');
        Route::post('/standards', [JshStandardController::class, 'update'])->name('standards.update');
        Route::post('/processes', [GreensandJshController::class, 'store'])->name('processes.store');
        Route::get('/processes/{id}', [GreensandJshController::class, 'show'])->whereNumber('id')->name('processes.show');
        Route::put('/processes/{id}', [GreensandJshController::class, 'update'])->whereNumber('id')->name('processes.update');
        Route::delete('/processes/{id}', [GreensandJshController::class, 'destroy'])->whereNumber('id')->name('processes.destroy');
    });

    Route::prefix('jsh-gfn')->name('jshgfn.')->group(function () {
        Route::get('/', [JshGfnPageController::class, 'index'])->name('index');
        Route::post('/', [JshGfnPageController::class, 'store'])->name('store');
        Route::put('/update', [JshGfnPageController::class, 'update'])->name('update');
        Route::post('/delete-today', [JshGfnPageController::class, 'deleteTodaySet'])->name('deleteToday');
        Route::get('/export', [JshGfnPageController::class, 'export'])->name('export');
        Route::get('/check-exists', [JshGfnPageController::class, 'checkExists'])->name('check-exists');
    });

    Route::view('/ace', 'ace.index')->name('ace.index');

    Route::prefix('ace')->name('ace.')->group(function () {
        Route::get('/data', [AceLineController::class, 'data'])->name('data');
        Route::get('/export', [AceLineController::class, 'export'])->name('export');
        Route::get('/summary', AceSummaryController::class)->name('summary');
        Route::post('/', [AceLineController::class, 'store'])->name('store');
        Route::get('/{id}', [AceLineController::class, 'show'])->whereNumber('id')->name('show');
        Route::put('/{id}', [AceLineController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [AceLineController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/standards', [AceStandardController::class, 'index'])->name('standards');
        Route::post('/standards', [AceStandardController::class, 'update'])->name('standards.update');
    });

    Route::prefix('aceline-gfn')->name('acelinegfn.')->group(function () {
        Route::get('/', [AceGfnPageController::class, 'index'])->name('index');
        Route::post('/store', [AceGfnPageController::class, 'store'])->name('store');
        Route::put('/update', [AceGfnPageController::class, 'update'])->name('update');
        Route::post('/delete-today', [AceGfnPageController::class, 'deleteTodaySet'])->name('deleteToday');
        Route::get('/export', [AceGfnPageController::class, 'export'])->name('export');
        Route::get('/check-exists', [AceGfnPageController::class, 'checkExists'])->name('check-exists');
    });
});
