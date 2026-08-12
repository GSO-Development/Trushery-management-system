<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('working_capital_loans', function (Blueprint $table) {
            $table->boolean('is_bank_confirmed')->default(false);
            $table->date('bank_confirmed_date')->nullable();
            $table->string('action_type', 50)->default('revise_iml');
            $table->string('settlement_type', 20)->nullable();
            $table->decimal('settled_amount', 15, 2)->nullable();
            $table->foreignId('settled_via_loan_id')->nullable()->constrained('working_capital_loans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('working_capital_loans', function (Blueprint $table) {
            $table->dropForeign(['settled_via_loan_id']);
            $table->dropColumn(['is_bank_confirmed', 'bank_confirmed_date', 'action_type', 'settlement_type', 'settled_amount', 'settled_via_loan_id']);
        });
    }
};
