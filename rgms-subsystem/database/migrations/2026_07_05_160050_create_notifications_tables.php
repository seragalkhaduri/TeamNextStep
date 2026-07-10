<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Notification Templates ──────────────────────────────
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->string('id')->primary(); // String key, e.g. 'PASSWORD_RESET', 'STUDENT_ENROLLED'
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('subject_en')->nullable();
            $table->string('subject_ar')->nullable();
            $table->text('body_en');
            $table->text('body_ar');
            $table->jsonb('channels'); // ['email', 'sms', 'in_app']
            $table->timestamps();
        });

        // ─── Notifications Log ───────────────────────────────────
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('recipient_type'); // User, Student, Employee
            $table->uuid('recipient_id');
            $table->string('template_id');
            $table->string('status')->default('PENDING'); // PENDING, SENT, FAILED
            $table->jsonb('channels_status'); // {'email': 'pending', 'sms': 'failed'}
            $table->jsonb('data')->nullable(); // JSON variables for template placeholders
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('notification_templates')->cascadeOnDelete();
            $table->index(['recipient_type', 'recipient_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_templates');
    }
};
