<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\api\Account\AccountController;

Route::prefix('accounts')->middleware(['ability:accounts.*'])->group(function () {
    // Standard CRUD routes
    Route::get('/', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/', [AccountController::class, 'store'])->name('accounts.store');
    Route::get('/select-list', [AccountController::class, 'selectList'])->name('accounts.select-list');
    Route::get('/types', [AccountController::class, 'getTypes'])->name('accounts.types');
    Route::get('/tree', [AccountController::class, 'tree'])->name('accounts.tree');
    Route::get('/chart', [AccountController::class, 'chart'])->name('accounts.chart');
    Route::get('/trial-balance', [AccountController::class, 'trialBalance'])->name('accounts.trial-balance');
    Route::post('/bulk-update', [AccountController::class, 'bulkUpdate'])->name('accounts.bulk-update');
    Route::post('/export', [AccountController::class, 'export'])->name('accounts.export');
    Route::post('/import', [AccountController::class, 'import'])->name('accounts.import');
    
    // Individual account routes
    Route::get('/{id}', [AccountController::class, 'show'])->name('accounts.show');
    Route::put('/{id}', [AccountController::class, 'update'])->name('accounts.update');
    Route::delete('/{id}', [AccountController::class, 'destroy'])->name('accounts.destroy');
    Route::patch('/{id}/toggle-status', [AccountController::class, 'toggleStatus'])->name('accounts.toggle-status');
    Route::get('/{id}/balance', [AccountController::class, 'getBalance'])->name('accounts.balance');
    Route::get('/{id}/hierarchy', [AccountController::class, 'hierarchy'])->name('accounts.hierarchy');
    Route::get('/{id}/summary', [AccountController::class, 'summary'])->name('accounts.summary');
    
    // Soft delete operations (admin only)
    Route::post('/{id}/restore', [AccountController::class, 'restore'])->name('accounts.restore');
    Route::delete('/{id}/force', [AccountController::class, 'forceDelete'])->name('accounts.force-delete');
});