<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('bank_id')->constrained('banks')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('interest_rate', 6, 3)->nullable()->comment('Annual interest rate %');
            $table->decimal('available_amount', 18, 2)->nullable()->comment('Available credit in LKR');
            $table->text('notes')->nullable();
            $table->timestamp('entry_date')->useCurrent();
            $table->timestamps();

            // One entry per company+bank (latest wins via update)
            $table->unique(['company_id', 'bank_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_entries');
    }
};
