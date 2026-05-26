<?php

use App\Http\Controllers\Admin\ApplicationBundleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DataSourceController;
use App\Http\Controllers\HsDashboardController;
use App\Http\Controllers\HsPlayerController;
use App\Http\Controllers\NcaaDashboardController;
use App\Http\Controllers\NcaaDataSourceController;
use App\Http\Controllers\NcaaPlayerController;
use App\Http\Controllers\NoteInputController;
use App\Http\Controllers\PlayerListController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkingBoardController;
use App\Support\ApplicationDatabaseBootstrap;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    if (ApplicationDatabaseBootstrap::needsFirstRunSetup()) {
        return redirect()->route('setup');
    }

    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/board', [WorkingBoardController::class, 'index'])->name('board.index');
    Route::get('/ncaa', [NcaaDashboardController::class, 'index'])->name('ncaa.index');
    Route::get('/ncaa/players/{player}', [NcaaPlayerController::class, 'show'])->name('ncaa.players.show');
    Route::get('/hs', [HsDashboardController::class, 'index'])->name('hs.index');
    Route::get('/hs/players/{player}', [HsPlayerController::class, 'show'])->name('hs.players.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('admin')->group(function () {
        Route::patch('/board', [WorkingBoardController::class, 'update'])->name('board.update');
        Route::get('/players', [PlayerListController::class, 'index'])->name('players.index');
        Route::post('/players', [PlayerListController::class, 'store'])->name('players.store');
        Route::patch('/players/{player}', [PlayerListController::class, 'update'])->name('players.update');
        Route::delete('/players/{player}', [PlayerListController::class, 'destroy'])->name('players.destroy');
        Route::get('/notes', [NoteInputController::class, 'index'])->name('notes.index');
        Route::patch('/notes', [NoteInputController::class, 'updateAll'])->name('notes.update-all');
        Route::patch('/notes/section', [NoteInputController::class, 'updateSection'])->name('notes.update-section');
        Route::delete('/notes/section', [NoteInputController::class, 'destroySection'])->name('notes.destroy-section');
        Route::get('/data-sources', [DataSourceController::class, 'index'])->name('data-sources.index');
        Route::post('/data-sources', [DataSourceController::class, 'store'])->name('data-sources.store');
        Route::get('/data-sources/uploads/{dataSourceUpload}/player-names', [DataSourceController::class, 'playerNames'])->name('data-sources.uploads.player-names');
        Route::get('/data-sources/uploads/{dataSourceUpload}/table-data', [DataSourceController::class, 'tableData'])->name('data-sources.uploads.table-data');
        Route::get('/data-sources/uploads/{dataSourceUpload}/group-values', [DataSourceController::class, 'groupColumnValues'])->name('data-sources.uploads.group-values');
        Route::patch('/data-sources/uploads/{dataSourceUpload}/settings', [DataSourceController::class, 'updateSettings'])->name('data-sources.uploads.settings');
        Route::post('/data-sources/uploads/{dataSourceUpload}/rows', [DataSourceController::class, 'storeRow'])->name('data-sources.uploads.rows.store');
        Route::patch('/data-sources/uploads/{dataSourceUpload}/rows/{ordinal}', [DataSourceController::class, 'updateRow'])->name('data-sources.uploads.rows.update')->whereNumber('ordinal');
        Route::delete('/data-sources/uploads/{dataSourceUpload}/rows/{ordinal}', [DataSourceController::class, 'destroyRow'])->name('data-sources.uploads.rows.destroy')->whereNumber('ordinal');
        Route::delete('/data-sources/uploads/{dataSourceUpload}', [DataSourceController::class, 'destroyUpload'])->name('data-sources.uploads.delete');
        Route::get('/data-sources/uploads/{dataSourceUpload}', [DataSourceController::class, 'show'])->name('data-sources.uploads.show');

        Route::get('/ncaa-data-sources', [NcaaDataSourceController::class, 'index'])->name('ncaa-data-sources.index');
        Route::post('/ncaa-data-sources', [NcaaDataSourceController::class, 'store'])->name('ncaa-data-sources.store');
        Route::get('/ncaa-data-sources/uploads/{dataSourceUpload}/player-names', [NcaaDataSourceController::class, 'playerNames'])->name('ncaa-data-sources.uploads.player-names');
        Route::get('/ncaa-data-sources/uploads/{dataSourceUpload}/table-data', [NcaaDataSourceController::class, 'tableData'])->name('ncaa-data-sources.uploads.table-data');
        Route::get('/ncaa-data-sources/uploads/{dataSourceUpload}/group-values', [NcaaDataSourceController::class, 'groupColumnValues'])->name('ncaa-data-sources.uploads.group-values');
        Route::patch('/ncaa-data-sources/uploads/{dataSourceUpload}/settings', [NcaaDataSourceController::class, 'updateSettings'])->name('ncaa-data-sources.uploads.settings');
        Route::post('/ncaa-data-sources/uploads/{dataSourceUpload}/rows', [NcaaDataSourceController::class, 'storeRow'])->name('ncaa-data-sources.uploads.rows.store');
        Route::patch('/ncaa-data-sources/uploads/{dataSourceUpload}/rows/{ordinal}', [NcaaDataSourceController::class, 'updateRow'])->name('ncaa-data-sources.uploads.rows.update')->whereNumber('ordinal');
        Route::delete('/ncaa-data-sources/uploads/{dataSourceUpload}/rows/{ordinal}', [NcaaDataSourceController::class, 'destroyRow'])->name('ncaa-data-sources.uploads.rows.destroy')->whereNumber('ordinal');
        Route::delete('/ncaa-data-sources/uploads/{dataSourceUpload}', [NcaaDataSourceController::class, 'destroyUpload'])->name('ncaa-data-sources.uploads.delete');
        Route::get('/ncaa-data-sources/uploads/{dataSourceUpload}', [NcaaDataSourceController::class, 'show'])->name('ncaa-data-sources.uploads.show');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/restore-data', [ApplicationBundleController::class, 'show'])->name('application-bundle.show');
            Route::get('/restore-data/download', [ApplicationBundleController::class, 'download'])->name('application-bundle.download');
            Route::post('/restore-data', [ApplicationBundleController::class, 'store'])->name('application-bundle.store');
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
        });
    });
});

require __DIR__.'/auth.php';
