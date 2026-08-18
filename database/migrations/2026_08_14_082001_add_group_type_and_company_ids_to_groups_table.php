<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->string('group_type')->default('individual')->after('name');
            $table->json('company_ids')->nullable()->after('group_type');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['group_type', 'company_ids']);
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
        });
    }
};