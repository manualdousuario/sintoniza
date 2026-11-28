<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

// ------------------------------------------------------------- Public
Route::get('/', HomeController::class)->name('home');

Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'showRegister'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/forget-password', [ForgotPasswordController::class, 'show'])->name('password.request');
Route::post('/forget-password', [ForgotPasswordController::class, 'send'])->name('password.email');
Route::get('/forget-password/reset', [ResetPasswordController::class, 'show'])->name('password.reset');
Route::post('/forget-password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

Route::post('/language', LanguageController::class)->name('language');

// -------------------------------------------------------- Authenticated
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AccountController::class, 'show'])->name('dashboard');
    Route::delete('/dashboard/subscriptions/{id}', [AccountController::class, 'unsubscribe'])
        ->whereNumber('id')
        ->name('account.unsubscribe');
    Route::post('/dashboard/preferences', [AccountController::class, 'updatePreferences'])->name('account.preferences');
    Route::post('/dashboard/password', [AccountController::class, 'updatePassword'])->name('account.password');

    // v3 pages, folded into the account page.
    Route::permanentRedirect('/dashboard/profile', '/dashboard');
    Route::permanentRedirect('/dashboard/devices', '/dashboard');
    Route::permanentRedirect('/dashboard/profile/devices', '/dashboard');
});
