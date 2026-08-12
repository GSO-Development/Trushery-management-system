<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Daily Bank Account Cash Position Entries ──────────────────────
        Schema::create('cash_position_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('bank_id')->constrained('banks')->onDelete('cascade');
            $table->foreignId('company_bank_account_id')->nullable()->constrained('company_bank_accounts')->onDelete('cascade');
            $table->date('entry_date');

            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('cash_in', 15, 2)->default(0);
            $table->decimal('cash_out', 15, 2)->default(0);
            $table->decimal('restricted_cash', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);

            $table->string('currency', 10)->default('LKR');
            $table->text('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['company_id', 'entry_date']);
        });

        // ── 2. Daily Cash Movement Breakdown Entries ────────────────────────
        Schema::create('cash_movement_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->date('entry_date');

            $table->decimal('customer_collections', 15, 2)->default(0);
            $table->decimal('loan_drawdowns', 15, 2)->default(0);
            $table->decimal('supplier_payments', 15, 2)->default(0);
            $table->decimal('salaries', 15, 2)->default(0);
            $table->decimal('taxes', 15, 2)->default(0);
            $table->decimal('loan_repayments', 15, 2)->default(0);
            $table->decimal('other_payments', 15, 2)->default(0);

            $table->text('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['company_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movement_entries');
        Schema::dropIfExists('cash_position_entries');
    }
};
