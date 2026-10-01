<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketAssignmentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketPriorityController;
use App\Http\Controllers\TicketStatusController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tickets');

Route::middleware('auth')->group(function () {
    // Breeze redirects here after login; the ticket list is the home page for every role.
    Route::redirect('/dashboard', '/tickets')->name('dashboard');

    Route::resource('tickets', TicketController::class);
    Route::patch('/tickets/{ticket}/status', TicketStatusController::class)->name('tickets.status');
    Route::patch('/tickets/{ticket}/priority', TicketPriorityController::class)->name('tickets.priority');
    Route::patch('/tickets/{ticket}/assignment', TicketAssignmentController::class)->name('tickets.assignment');
    Route::post('/tickets/{ticket}/comments', [CommentController::class, 'store'])->name('tickets.comments.store');
    Route::get('/attachments/{attachment}', AttachmentController::class)->name('attachments.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::resource('categories', CategoryController::class)->except('show');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
    });
});

require __DIR__.'/auth.php';
