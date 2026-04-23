<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DataSourceController;
use App\Http\Controllers\HsDashboardController;
use App\Http\Controllers\HsPlayerController;
use App\Http\Controllers\NcaaDashboardController;
use App\Http\Controllers\NcaaPlayerController;
use App\Http\Controllers\NoteInputController;
use App\Http\Controllers\PlayerListController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkingBoardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/board', [WorkingBoardController::class, 'index'])->name('board.index');
    Route::get('/players', [PlayerListController::class, 'index'])->name('players.index');
    Route::post('/players', [PlayerListController::class, 'store'])->name('players.store');
    Route::delete('/players/{player}', [PlayerListController::class, 'destroy'])->name('players.destroy');
    Route::get('/ncaa', [NcaaDashboardController::class, 'index'])->name('ncaa.index');
    Route::get('/ncaa/players/{player}', [NcaaPlayerController::class, 'show'])->name('ncaa.players.show');
    Route::get('/hs', [HsDashboardController::class, 'index'])->name('hs.index');
    Route::get('/hs/players/{player}', [HsPlayerController::class, 'show'])->name('hs.players.show');
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

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
    });
});

require __DIR__.'/auth.php';
