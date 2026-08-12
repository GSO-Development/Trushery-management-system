<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Loan Repayment Schedules ─────────────────────────────────────
        Schema::create('loan_repayment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('bank_id')->constrained('banks')->onDelete('cascade');
            $table->string('loan_category', 50); // Long Term Loan, Working Capital
            $table->unsignedBigInteger('loan_id')->nullable();
            $table->date('due_date');
            $table->decimal('principal_amount', 15, 2)->default(0);
            $table->decimal('interest_amount', 15, 2)->default(0);
            $table->decimal('total_installment', 15, 2)->default(0);
            $table->string('status', 20)->default('Pending'); // Pending, Paid, Overdue
            $table->date('paid_date')->nullable();
            $table->string('currency', 10)->default('LKR');
            $table->text('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['company_id', 'due_date']);
        });

        // ── 2. Facility Transactions History ────────────────────────────────
        Schema::create('facility_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('bank_id')->constrained('banks')->onDelete('cascade');
            $table->string('facility_category', 50); // Long Term Loan, Working Capital, Fixed Deposit
            $table->string('transaction_type', 50); // Drawdown, Repayment, Interest Payment, Fee
            $table->string('reference_number', 100)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('transaction_date');
            $table->string('currency', 10)->default('LKR');
            $table->text('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['company_id', 'transaction_date']);
        });

        // ── 3. Bank Interest Rate Master ────────────────────────────────────
        Schema::create('bank_rate_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('bank_id')->constrained('banks')->onDelete('cascade');
            $table->string('rate_type', 50)->default('Fixed'); // AWPLR, SLFR, SOFR, Fixed, Custom
            $table->decimal('base_rate', 8, 3)->default(0);
            $table->decimal('margin', 8, 3)->default(0);
            $table->decimal('effective_rate', 8, 3)->default(0);
            $table->date('effective_date');
            $table->text('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['company_id', 'effective_date']);
        });

        // ── 4. Audit Logs ───────────────────────────────────────────────────
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('user_name', 150)->nullable();
            $table->string('action', 50); // CREATE, UPDATE, DELETE, LOGIN
            $table->string('module', 50); // Long Term Loans, Working Capital, Fixed Deposits, Cash Position, Rates, Schedules, Transactions
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('bank_rate_masters');
        Schema::dropIfExists('facility_transactions');
        Schema::dropIfExists('loan_repayment_schedules');
    }
};
