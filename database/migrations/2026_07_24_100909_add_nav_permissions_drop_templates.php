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
        // Add nav_permissions JSON column to groups
        Schema::table('groups', function (Blueprint $table) {
            $table->json('nav_permissions')->nullable()->after('name');
        });

        // Drop the old pivot table first (has foreign key to templates)
        Schema::dropIfExists('group_template');

        // Drop the templates table
        Schema::dropIfExists('templates');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore templates table
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('blade_path');
            $table->timestamps();
        });

        // Restore pivot table
        Schema::create('group_template', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->primary(['group_id', 'template_id']);
        });

        // Remove nav_permissions column
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('nav_permissions');
        });
    }
};
