<?php

use App\Http\Controllers\Auth\MicrosoftAuthController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// -----------------------------------------------------------------------------
// Public Welcome Page
// -----------------------------------------------------------------------------
Route::view('/', 'welcome')->name('home');

// -----------------------------------------------------------------------------
// Microsoft Azure AD OAuth Routes
// -----------------------------------------------------------------------------
Route::get('/auth/microsoft', [MicrosoftAuthController::class, 'redirect'])
    ->name('auth.microsoft');

Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])
    ->name('auth.microsoft.callback');

// -----------------------------------------------------------------------------
// Admin Routes – Only accessible to users with is_admin = true
// -----------------------------------------------------------------------------
Route::middleware(['auth', 'is_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Volt::route('/', 'admin.dashboard')
            ->name('dashboard');

        Volt::route('/users', 'admin.users.index')
            ->name('users.index');

        Volt::route('/users/create', 'admin.users.form')
            ->name('users.create');

        Volt::route('/users/{user}/edit', 'admin.users.form')
            ->name('users.edit');

        Volt::route('/companies', 'admin.companies.index')
            ->name('companies.index');

        Volt::route('/banks', 'admin.banks.index')
            ->name('banks.index');

        Volt::route('/groups', 'admin.groups.index')
            ->name('groups.index');

        Volt::route('/settings', 'admin.settings.index')
            ->name('settings');

        Route::get('/company-comparison', [\App\Http\Controllers\Ceo\GroupComparisonController::class, 'index'])
            ->name('comparison');
    });

// -----------------------------------------------------------------------------
// Group Routes – Multi-company executive overview dashboard & Comparison tool
// -----------------------------------------------------------------------------
Route::middleware(['auth', 'ceo.access'])
    ->prefix('group')
    ->name('group.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Ceo\CeoDashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('/comparison', [\App\Http\Controllers\Ceo\GroupComparisonController::class, 'index'])
            ->name('comparison');
        Route::get('/company/{company_slug}', [\App\Http\Controllers\Ceo\CeoDashboardController::class, 'subcompanyDashboard'])
            ->name('company.dashboard');
        Route::get('/notifications', [\App\Http\Controllers\Ceo\CeoNotificationController::class, 'index'])
            ->name('notifications');
        Route::post('/notifications/dispatch', [\App\Http\Controllers\Ceo\CeoNotificationController::class, 'dispatchEmails'])
            ->name('notifications.dispatch');
    });

// Legacy /ceo/* URL redirects to /group/*
Route::middleware(['auth'])->prefix('ceo')->group(function () {
    Route::get('/dashboard', fn() => redirect()->route('group.dashboard'))->name('ceo.dashboard');
    Route::get('/comparison', fn() => redirect()->route('group.comparison'))->name('ceo.comparison');
    Route::get('/company/{company_slug}', fn($slug) => redirect()->route('group.company.dashboard', $slug));
    Route::get('/notifications', fn() => redirect()->route('group.notifications'))->name('ceo.notifications');
});

// -----------------------------------------------------------------------------
// Tenant Routes – MVC Controller-based, one route per page type
// -----------------------------------------------------------------------------
require __DIR__ . '/tenant.php';

// -----------------------------------------------------------------------------
// Auth routes (login, password reset, etc.)
// -----------------------------------------------------------------------------
require __DIR__ . '/auth.php';