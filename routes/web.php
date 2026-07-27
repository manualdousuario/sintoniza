<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use App\Livewire\Dashboard\AddSubscription;
use App\Livewire\Dashboard\Devices;
use App\Livewire\Dashboard\Index;
use App\Livewire\Dashboard\LatestUpdates;
use App\Livewire\Dashboard\Profile;
use App\Livewire\Subscription\Episode;
use App\Livewire\Subscription\Show;
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
    Route::get('/dashboard', Index::class)->name('dashboard');
    Route::get('/dashboard/add', AddSubscription::class)->name('dashboard.add');
    Route::get('/dashboard/profile', Profile::class)->name('dashboard.profile');
    Route::get('/dashboard/profile/latest-updates', LatestUpdates::class)->name('dashboard.latest-updates');
    Route::get('/dashboard/profile/devices', Devices::class)->name('dashboard.devices');

    Route::get('/subscription/{id}', Show::class)->name('subscription.show');
    Route::get('/subscription/{id}/episode/{episodeId}', Episode::class)->name('subscription.episode');
});
