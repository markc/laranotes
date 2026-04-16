<?php

use App\Http\Controllers\CollabController;
use App\Http\Middleware\VerifyCollabServer;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifyCollabServer::class)->prefix('collab')->group(function () {
    Route::get('/notes/{note}/body', [CollabController::class, 'body']);
    Route::put('/notes/{note}/body', [CollabController::class, 'saveBody']);
});
