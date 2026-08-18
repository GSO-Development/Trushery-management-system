<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('banks', 'short_name')) {
            Schema::table('banks', function (Blueprint $table) {
                $table->string('short_name', 30)->nullable()->after('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('banks', 'short_name')) {
            Schema::table('banks', function (Blueprint $table) {
                $table->dropColumn('short_name');
            });
        }
    }
};