<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Subsystems ──────────────────────────────────────────
        Schema::create('subsystems', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_en');
            $table->string('name_ar');
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('api_key_hash')->unique();
            $table->string('status')->default('ACTIVE'); // Active, Suspended, DeveloperOnly
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->string('contact_email');
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Add FK to audit_logs ────────────────────────────────
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('actor_subsystem_id')
                  ->references('id')
                  ->on('subsystems')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['actor_subsystem_id']);
        });

        Schema::dropIfExists('subsystems');
    }
};
