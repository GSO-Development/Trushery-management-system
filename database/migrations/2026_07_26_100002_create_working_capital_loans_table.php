<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 3.2 Working Capital Loans
     * Matches: Excel sheet "3. Loans" → "3.2 Working Capital Loan" section
     *
     * Fields: Company, Bank, Facility Type, Tenor, Facility Amount,
     *         Obtained Date, Settlement Date, Settlement Days Passed (overdue),
     *         No. of Days Extended, Revised Settlement Date, Rate, Outstanding, Currency
     */
    public function up(): void
    {
        Schema::create('working_capital_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('bank_id')->constrained('banks')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');

            $table->string('facility_type', 100)->comment('e.g. PCL USD, PCL LKR, IML, STL, Overdraft, Money Market Loan, Credit Card');
            $table->string('tenor', 50)->nullable()->comment('e.g. 90D, 120D, 150D, 270D, On Demand');
            $table->decimal('facility_amount', 18, 2)->default(0);
            $table->date('obtained_date')->nullable();
            $table->date('settlement_date')->nullable();
            $table->integer('settlement_days_overdue')->default(0)->comment('Auto-calculated: days past settlement date');
            $table->integer('days_extended')->default(0)->comment('No. of days extended beyond original settlement');
            $table->date('revised_settlement_date')->nullable();
            $table->decimal('interest_rate', 6, 3)->default(0)->comment('Annual rate %');
            $table->decimal('outstanding_amount', 18, 2)->default(0);
            $table->enum('currency', ['LKR', 'USD'])->default('LKR');
            $table->text('notes')->nullable();

            $table->date('entry_date')->comment('Date of this data entry');
            $table->timestamps();

            $table->index(['company_id', 'bank_id']);
            $table->index(['company_id', 'entry_date']);
            $table->index(['company_id', 'settlement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('working_capital_loans');
    }
};
