<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('remember_token')->constrained('companies')->onDelete('set null');
            $table->foreignId('group_id')->nullable()->after('company_id')->constrained('groups')->onDelete('set null');
            $table->boolean('is_admin')->default(false)->after('group_id');
            $table->string('azure_id')->nullable()->after('is_admin');
            $table->string('auth_provider')->default('local')->after('azure_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['group_id']);
            $table->dropColumn(['company_id', 'group_id', 'is_admin', 'azure_id', 'auth_provider']);
        });
    }
};
