<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates company_bank_accounts table for storing bank account details
     * per company, from the "Daily Group Cash Position Report" summary sheet.
     */
    public function up(): void
    {
        Schema::create('company_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_id')->constrained()->cascadeOnDelete();
            $table->string('account_type', 50);   // Current, Saving, BFCA, BLA, C/A, MMS, etc.
            $table->string('account_number', 100);
            $table->string('currency', 10)->default('LKR'); // LKR, USD, EUR, GBP
            $table->string('notes', 255)->nullable();       // any extra remarks
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_bank_accounts');
    }
};
