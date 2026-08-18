<?php

use App\Http\Controllers\Tenant\AuditLogController;
use App\Http\Controllers\Tenant\FixedDepositController;
use App\Http\Controllers\Tenant\LongTermLoanController;
use App\Http\Controllers\Tenant\NotificationController;
use App\Http\Controllers\Tenant\ProfileController;
use App\Http\Controllers\Tenant\TenantController;
use App\Http\Controllers\Tenant\WorkingCapitalController;
use Illuminate\Support\Facades\Route;

// Tenant Routes - Clean 5-Module Sub-Company Portal
Route::middleware(['auth', 'tenant.access'])
    ->prefix('{company_slug}')
    ->name('tenant.')
    ->group(function () {

        // User Profile & Account Settings
        Route::get('/profile', [ProfileController::class, 'index'])
            ->name('profile');
        Route::post('/profile/password', [ProfileController::class, 'updatePassword'])
            ->name('profile.password');

        // 0. Daily Group Cash Position Report
        Route::get('/cash-position', [App\Http\Controllers\Tenant\CashPositionController::class, 'index'])
            ->name('cash-position');
        Route::post('/cash-position/entry', [App\Http\Controllers\Tenant\CashPositionController::class, 'storeEntry'])
            ->name('cash-position.entry');
        Route::post('/cash-position/movement', [App\Http\Controllers\Tenant\CashPositionController::class, 'storeMovement'])
            ->name('cash-position.movement');
        Route::post('/cash-position/bank-account', [App\Http\Controllers\Tenant\CashPositionController::class, 'storeBankAccount'])
            ->name('cash-position.bank-account');
        Route::delete('/cash-position/bank-account/{bankAccount}', [App\Http\Controllers\Tenant\CashPositionController::class, 'destroyBankAccount'])
            ->name('cash-position.bank-account.destroy');
        Route::delete('/cash-position/entry/{entry}', [App\Http\Controllers\Tenant\CashPositionController::class, 'destroyEntry'])
            ->name('cash-position.entry.destroy');

        // 1. Summary Dashboard
        Route::get('/summary-dashboard', [TenantController::class, 'summaryDashboard'])
            ->name('summary-dashboard');

        // 2. Long Term Loans
        Route::get('/long-term-loans', [LongTermLoanController::class, 'index'])
            ->name('long-term-loans');
        Route::post('/long-term-loans', [LongTermLoanController::class, 'store'])
            ->name('long-term-loans.store');
        Route::post('/long-term-loans/{loan}/update-rate', [LongTermLoanController::class, 'updateRate'])
            ->name('long-term-loans.update-rate');
        Route::delete('/long-term-loans/{loan}', [LongTermLoanController::class, 'destroy'])
            ->name('long-term-loans.destroy');

        // 3. Working Capital Loan
        Route::get('/working-capital', [WorkingCapitalController::class, 'index'])
            ->name('working-capital');
        Route::post('/working-capital', [WorkingCapitalController::class, 'store'])
            ->name('working-capital.store');
        Route::post('/working-capital/{workingCapitalLoan}/update-rate', [WorkingCapitalController::class, 'updateRate'])
            ->name('working-capital.update-rate');
        Route::delete('/working-capital/{workingCapitalLoan}', [WorkingCapitalController::class, 'destroy'])
            ->name('working-capital.destroy');

        // 4. Fixed Deposits
        Route::get('/fixed-deposits', [FixedDepositController::class, 'index'])
            ->name('fixed-deposits');
        Route::post('/fixed-deposits', [FixedDepositController::class, 'store'])
            ->name('fixed-deposits.store');
        Route::post('/fixed-deposits/{fixedDeposit}/update-rate', [FixedDepositController::class, 'updateRate'])
            ->name('fixed-deposits.update-rate');
        Route::delete('/fixed-deposits/{fixedDeposit}', [FixedDepositController::class, 'destroy'])
            ->name('fixed-deposits.destroy');

        // 5. Audit Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs');

        // 6. Notifications & Expiry Alerts (Universal for all Sub-Companies)
        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications');
        Route::post('/notifications/dispatch', [NotificationController::class, 'dispatchEmails'])
            ->name('notifications.dispatch');
    });
