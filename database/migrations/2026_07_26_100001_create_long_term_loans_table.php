<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 3.1 Long Term Loans
     * Matches: Excel sheet "3. Loans" → "3.1 Long Term Loan" section
     *
     * Fields: Company, Bank, Loan Type, Tenor, Facility Amount,
     *         Initially Granted/Restructured Date, Current Interest Rate,
     *         Remaining Tenor (Months), Outstanding Amount, Currency, Notes
     */
    public function up(): void
    {
        Schema::create('long_term_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('bank_id')->constrained('banks')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');

            $table->string('loan_type', 100)->comment('e.g. Capex funding, Vehicle, Term Loan, Moratorium Loan');
            $table->string('tenor', 100)->comment('e.g. 48 Months, 10 Years');
            $table->decimal('facility_amount', 18, 2)->default(0);
            $table->date('granted_date')->nullable()->comment('Initially granted or restructured date');
            $table->decimal('interest_rate', 6, 3)->default(0)->comment('Annual rate %');
            $table->integer('remaining_tenor_months')->nullable()->comment('Remaining tenor in months');
            $table->decimal('outstanding_amount', 18, 2)->default(0);
            $table->enum('currency', ['LKR', 'USD'])->default('LKR');
            $table->text('notes')->nullable();

            $table->date('entry_date')->comment('Date of this data entry');
            $table->timestamps();

            $table->index(['company_id', 'bank_id']);
            $table->index(['company_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('long_term_loans');
    }
};
