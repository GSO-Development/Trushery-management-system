<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add revision history columns to long_term_loans, working_capital_loans, and fixed_deposits
     */
    public function up(): void
    {
        // Long Term Loans
        Schema::table('long_term_loans', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('user_id')->comment('Points to original entry if this is a historical revision');
            $table->boolean('is_active')->default(true)->after('parent_id')->comment('False if archived history version');
            $table->unsignedInteger('version')->default(1)->after('is_active')->comment('Revision version number');
            $table->text('revision_notes')->nullable()->after('notes')->comment('Reason or details for rate/term revision');
            $table->date('revision_date')->nullable()->after('revision_notes')->comment('Date rate revision took effect');

            $table->index(['company_id', 'is_active']);
            $table->index('parent_id');
        });

        // Working Capital Loans
        Schema::table('working_capital_loans', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('user_id')->comment('Points to original entry if this is a historical revision');
            $table->boolean('is_active')->default(true)->after('parent_id')->comment('False if archived history version');
            $table->unsignedInteger('version')->default(1)->after('is_active')->comment('Revision version number');
            $table->text('revision_notes')->nullable()->after('notes')->comment('Reason or details for rate/term revision');
            $table->date('revision_date')->nullable()->after('revision_notes')->comment('Date rate revision took effect');

            $table->index(['company_id', 'is_active']);
            $table->index('parent_id');
        });

        // Fixed Deposits
        Schema::table('fixed_deposits', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('user_id')->comment('Points to original entry if this is a historical revision');
            $table->boolean('is_active')->default(true)->after('parent_id')->comment('False if archived history version');
            $table->unsignedInteger('version')->default(1)->after('is_active')->comment('Revision version number');
            $table->text('revision_notes')->nullable()->after('pledged_details')->comment('Reason or details for rate/term revision');
            $table->date('revision_date')->nullable()->after('revision_notes')->comment('Date rate revision took effect');

            $table->index(['company_id', 'is_active']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('long_term_loans', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'is_active', 'version', 'revision_notes', 'revision_date']);
        });

        Schema::table('working_capital_loans', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'is_active', 'version', 'revision_notes', 'revision_date']);
        });

        Schema::table('fixed_deposits', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'is_active', 'version', 'revision_notes', 'revision_date']);
        });
    }
};
