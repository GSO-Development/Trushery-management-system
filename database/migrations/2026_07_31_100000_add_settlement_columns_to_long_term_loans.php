<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('long_term_loans', function (Blueprint $table) {
            $table->string('settlement_type', 20)->nullable();
            $table->decimal('settled_amount', 15, 2)->nullable();
            $table->foreignId('settled_via_loan_id')->nullable()->constrained('long_term_loans')->nullOnDelete();
            $table->string('action_type', 50)->default('rate_change');
        });
    }

    public function down(): void
    {
        Schema::table('long_term_loans', function (Blueprint $table) {
            $table->dropForeign(['settled_via_loan_id']);
            $table->dropColumn(['settlement_type', 'settled_amount', 'settled_via_loan_id', 'action_type']);
        });
    }
};
