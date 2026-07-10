<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Audit logs table (SDD §3.4, §4.2, FR-AUD-002).
 *
 * IMMUTABLE, APPEND-ONLY:
 * - No updated_at column
 * - No soft deletes
 * - DB-level restriction: dedicated user with INSERT/SELECT only
 * - Application-level: AuditLog model throws on update/delete
 *
 * Indexes per SDD §4.3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event_type');     // e.g. DATA_CHANGE, USER_MANAGEMENT, SUBSYSTEM_EVENT
            $table->string('entity_type');     // e.g. Student, Employee, User
            $table->uuid('entity_id')->nullable();
            $table->string('action');          // CREATE, UPDATE, DELETE, LOGIN, LOGOUT, etc.
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_subsystem_id')->nullable();
            $table->jsonb('old_value')->nullable();
            $table->jsonb('new_value')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('record_hash')->nullable();       // SHA-256 chain (optional integrity)
            $table->string('prev_record_hash')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // NO updated_at — this table is append-only (SDD §3.4)
            // NO soft deletes — audit logs are never deleted

            // Foreign keys
            $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();
            // actor_subsystem_id FK will be added when subsystems table is created (Phase 6)
        });

        // Indexes per SDD §4.3
        if (config('database.default') === 'pgsql') {
            // Index on created_at DESC for chronological queries
            DB::statement('CREATE INDEX audit_logs_created_at_desc ON audit_logs (created_at DESC)');

            // Composite: actor + time for "what did user X do?" queries
            DB::statement('CREATE INDEX audit_logs_actor_user_created ON audit_logs (actor_user_id, created_at DESC)');

            // Composite: entity lookup for "history of entity Y"
            DB::statement('CREATE INDEX audit_logs_entity_created ON audit_logs (entity_type, entity_id, created_at DESC)');

            // GIN index on new_value JSONB for content-based searches
            DB::statement('CREATE INDEX audit_logs_new_value_gin ON audit_logs USING GIN (new_value jsonb_path_ops)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
