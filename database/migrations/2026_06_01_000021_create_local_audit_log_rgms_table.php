<?php

declare(strict_types=1);

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
        Schema::create('local_audit_log_rgms', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->enum('action', [
                'CREATE', 'UPDATE', 'DELETE', 'TRANSITION', 'ACCESS', 'EXPORT', 'LOGIN',
            ]);
            $table->string('entity_type', 100);
            $table->uuid('entity_id')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->string('user_id', 100);
            $table->string('user_role', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            // Server-side UTC timestamp — never client-provided (SDD §4.2.13).
            $table->timestamp('recorded_at')->useCurrent();

            $table->index(['entity_type', 'entity_id', 'recorded_at'], 'idx_audit_entity');
            $table->index(['user_id', 'recorded_at'], 'idx_audit_user');
            $table->index('action', 'idx_audit_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('local_audit_log_rgms');
    }
};