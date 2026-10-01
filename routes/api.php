<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TicketStatusController;
use App\Http\Controllers\AttachmentController;
use Illuminate\Support\Facades\Route;

// Rate limiters "login" and "api" are defined in AppServiceProvider.
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('auth.login');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::apiResource('tickets', TicketController::class)->only(['index', 'store', 'show']);
        Route::patch('/tickets/{ticket}/status', TicketStatusController::class)->name('tickets.status');
        Route::post('/tickets/{ticket}/comments', [CommentController::class, 'store'])->name('tickets.comments.store');
        Route::get('/attachments/{attachment}', AttachmentController::class)->name('attachments.show');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    });
});
