<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 3.3 Fixed Deposits / Other Deposits
     * Matches: Excel sheet "3.3 deposits"
     *
     * Fields: Company, Bank/Institute, Amount, Commencement Date,
     *         Maturity Date, Tenor (calculated), Rate,
     *         Renewal Instructions, Pledged Details, Currency
     */
    public function up(): void
    {
        Schema::create('fixed_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('bank_id')->constrained('banks')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');

            $table->decimal('amount', 18, 2)->default(0)->comment('Deposit amount');
            $table->enum('currency', ['LKR', 'USD'])->default('LKR');
            $table->date('commencement_date')->nullable()->comment('FD start date');
            $table->date('maturity_date')->nullable()->comment('FD maturity date');
            $table->string('tenor', 100)->nullable()->comment('e.g. 30 days, 90 days (calculated from dates)');
            $table->decimal('interest_rate', 6, 3)->default(0)->comment('FD interest rate %');
            $table->text('renewal_instructions')->nullable()->comment('e.g. Renew, Liquidate, Transfer');
            $table->text('pledged_details')->nullable()->comment('If pledged against a loan, enter details');

            $table->date('entry_date')->comment('Date of this data entry');
            $table->timestamps();

            $table->index(['company_id', 'bank_id']);
            $table->index(['company_id', 'maturity_date']);
            $table->index(['company_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_deposits');
    }
};
