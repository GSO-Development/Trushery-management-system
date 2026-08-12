<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Guest-only routes
Route::middleware('guest')->group(function () {
    // NOTE: Public registration is DISABLED — only admins can create users
    // Volt::route('register', 'pages.auth.register')->name('register');

    Volt::route('login', 'pages.auth.login')
        ->name('login');

    Volt::route('forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'pages.auth.reset-password')
        ->name('password.reset');
});

// Authenticated-only routes
Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');

    // Logout — used by admin & portal layout form buttons / direct links
    Route::match(['get', 'post'], 'logout', \App\Http\Controllers\Auth\LogoutController::class)
        ->name('logout');
});
