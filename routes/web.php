<?php

use App\Http\Controllers\Auth\MicrosoftAuthController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// ─────────────────────────────────────────────────────────────────────────────
// Public Welcome Page
// ─────────────────────────────────────────────────────────────────────────────
Route::view('/', 'welcome')->name('home');

// ─────────────────────────────────────────────────────────────────────────────
// Microsoft Azure AD OAuth Routes
// ─────────────────────────────────────────────────────────────────────────────
Route::get('/auth/microsoft', [MicrosoftAuthController::class, 'redirect'])
    ->name('auth.microsoft');

Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])
    ->name('auth.microsoft.callback');

// ─────────────────────────────────────────────────────────────────────────────
// Admin Routes — Only accessible to users with is_admin = true
// Uses Livewire Volt (admin panel is fully reactive single-page components)
// ─────────────────────────────────────────────────────────────────────────────
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

        Route::get('/company-comparison', [\App\Http\Controllers\Ceo\GroupComparisonController::class, 'index'])
            ->name('comparison');
    });

// ─────────────────────────────────────────────────────────────────────────────
// CEO Routes — Multi-company executive overview dashboard
// Business logic in App\Http\Controllers\Ceo\CeoDashboardController
// View in resources/views/ceo/dashboard.blade.php
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'ceo.access'])
    ->prefix('ceo')
    ->name('ceo.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Ceo\CeoDashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('/comparison', [\App\Http\Controllers\Ceo\GroupComparisonController::class, 'index'])
            ->name('comparison');
    });

// ─────────────────────────────────────────────────────────────────────────────
// Tenant Routes — MVC Controller-based, one route per page type
// Business logic is in App\Http\Controllers\Tenant\TenantController
// Views are pure Blade templates in resources/views/livewire/tenant/{company}/
// Loaded from routes/tenant.php
// ─────────────────────────────────────────────────────────────────────────────
require __DIR__ . '/tenant.php';

// ─────────────────────────────────────────────────────────────────────────────
// Auth routes (login, password reset, etc.)
// ─────────────────────────────────────────────────────────────────────────────
require __DIR__ . '/auth.php';
