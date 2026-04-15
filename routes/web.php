<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/notes/search', [NoteController::class, 'search'])->name('notes.search');
    Route::resource('notes', NoteController::class);

    Route::resource('folders', FolderController::class)->except(['show', 'create', 'edit']);
    Route::get('/api/folder-tree', [FolderController::class, 'tree'])->name('folders.tree');
});

require __DIR__.'/settings.php';
