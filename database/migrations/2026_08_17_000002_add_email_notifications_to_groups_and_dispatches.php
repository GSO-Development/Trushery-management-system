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
        // 1. Add email_notifications_enabled to groups table
        if (Schema::hasTable('groups')) {
            Schema::table('groups', function (Blueprint $table) {
                if (!Schema::hasColumn('groups', 'email_notifications_enabled')) {
                    $table->boolean('email_notifications_enabled')->default(false)->after('nav_permissions');
                }
            });
        }

        // 2. Create notification_dispatches table to track sent alert emails
        if (!Schema::hasTable('notification_dispatches')) {
            Schema::create('notification_dispatches', function (Blueprint $table) {
                $table->id();
                $table->string('alert_id')->index(); // e.g. 'fd-1', 'wc-3', 'ltl-2'
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('recipient_email');
                $table->string('subject');
                $table->string('status')->default('sent'); // 'sent', 'failed'
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->useCurrent();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_dispatches');

        if (Schema::hasTable('groups') && Schema::hasColumn('groups', 'email_notifications_enabled')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->dropColumn('email_notifications_enabled');
            });
        }
    }
};
