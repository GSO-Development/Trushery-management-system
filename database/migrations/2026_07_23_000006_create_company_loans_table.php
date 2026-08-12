<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('bank_name');
            $table->enum('loan_type', ['short_term', 'long_term']);
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('outstanding_balance', 15, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->decimal('monthly_installment', 15, 2);
            $table->date('due_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_loans');
    }
};
