<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_deposits', function (Blueprint $table) {
            $table->string('action_type', 50)->default('renew_revise');
            $table->string('withdrawal_type', 20)->nullable();
            $table->decimal('withdrawn_amount', 15, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('fixed_deposits', function (Blueprint $table) {
            $table->dropColumn(['action_type', 'withdrawal_type', 'withdrawn_amount']);
        });
    }
};
