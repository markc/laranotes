<?php

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\CollabController;
use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

// Public invite acceptance — no auth required, rate-limited to discourage
// token enumeration.
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/invite/{token}', [InviteController::class, 'show'])->name('invites.show');
    Route::post('/invite/{token}', [InviteController::class, 'accept'])->name('invites.accept');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/notes/search', [NoteController::class, 'search'])->name('notes.search');
    Route::resource('notes', NoteController::class);
    Route::get('/api/collab/token/{note}', [CollabController::class, 'token'])->name('collab.token');

    Route::resource('folders', FolderController::class)->except(['show', 'create', 'edit']);
    Route::get('/api/folder-tree', [FolderController::class, 'tree'])->name('folders.tree');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::middleware('no-impersonation')->group(function () {
            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
            Route::post('/impersonate/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');
            Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
            Route::patch('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
        });
    });

    // Stop route is outside role:admin — the admin is currently logged in
    // as the impersonated user, so their role is the target's, not admin.
    // The controller checks session('impersonator_id') directly.
    Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');

    Route::middleware(['role:admin,moderator', 'no-impersonation'])->group(function () {
        Route::get('/invites', [InviteController::class, 'index'])->name('invites.index');
        Route::post('/invites', [InviteController::class, 'store'])->name('invites.store');
        Route::delete('/invites/{invite}', [InviteController::class, 'destroy'])->name('invites.destroy');
    });
});

require __DIR__.'/settings.php';
