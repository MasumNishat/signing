<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EnvelopeController;
use App\Http\Controllers\Web\TemplateController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Homepage
Route::get('/', function () {
    return redirect('/dashboard');
})->middleware('auth');

// Authentication Routes (Guest Only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Dashboard Routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/widgets', [DashboardController::class, 'widgets'])->name('dashboard.widgets');
    Route::get('/dashboard/activity', [DashboardController::class, 'activity'])->name('dashboard.activity');

    // Envelope Routes
    Route::prefix('envelopes')->name('envelopes.')->group(function () {
        Route::get('/', [EnvelopeController::class, 'index'])->name('index');
        Route::get('/create', [EnvelopeController::class, 'create'])->name('create');
        Route::get('/{id}', [EnvelopeController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [EnvelopeController::class, 'edit'])->name('edit');
    });

    // Template Routes
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [TemplateController::class, 'index'])->name('index');
        Route::get('/create', [TemplateController::class, 'create'])->name('create');
        Route::get('/{id}', [TemplateController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [TemplateController::class, 'edit'])->name('edit');
    });
});
