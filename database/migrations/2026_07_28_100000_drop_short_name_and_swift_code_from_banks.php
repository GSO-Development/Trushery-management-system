<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            if (Schema::hasColumn('banks', 'short_name')) {
                $table->dropColumn('short_name');
            }
            if (Schema::hasColumn('banks', 'swift_code')) {
                $table->dropColumn('swift_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->string('short_name')->nullable();
            $table->string('swift_code')->nullable();
        });
    }
};
