<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EnvelopeController;
use App\Http\Controllers\Web\TemplateController;
use App\Http\Controllers\Web\DocumentController;
use App\Http\Controllers\Web\RecipientController;
use App\Http\Controllers\Web\ContactController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\BillingController;

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

    // Document Routes (Phase F5)
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::get('/upload', [DocumentController::class, 'upload'])->name('upload');
        Route::get('/{id}/viewer', [DocumentController::class, 'viewer'])->name('viewer');
    });

    // Recipient Routes (Phase F5)
    Route::prefix('recipients')->name('recipients.')->group(function () {
        Route::get('/', [RecipientController::class, 'index'])->name('index');
        Route::get('/create', [RecipientController::class, 'create'])->name('create');
        Route::get('/{id}/edit', [RecipientController::class, 'edit'])->name('edit');
    });

    // Contact Routes (Phase F5)
    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::get('/', [ContactController::class, 'index'])->name('index');
        Route::get('/create', [ContactController::class, 'create'])->name('create');
    });

    // User Routes (Phase F6)
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::get('/profile', [UserController::class, 'profile'])->name('profile');
        Route::get('/{id}', [UserController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');
    });

    // Settings Routes (Phase F6)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/account', [SettingsController::class, 'account'])->name('account');
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::get('/security', [SettingsController::class, 'security'])->name('security');
        Route::get('/branding', [SettingsController::class, 'branding'])->name('branding');
    });

    // Billing Routes (Phase F6)
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [BillingController::class, 'index'])->name('index');
        Route::get('/plans', [BillingController::class, 'plans'])->name('plans');
        Route::get('/invoices', [BillingController::class, 'invoices'])->name('invoices');
        Route::get('/payments', [BillingController::class, 'payments'])->name('payments');
    });
});
