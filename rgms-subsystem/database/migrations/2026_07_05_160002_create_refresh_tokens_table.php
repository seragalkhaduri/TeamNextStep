<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refresh tokens table (SDD §6.2).
 *
 * Sanctum doesn't have native refresh tokens. This custom table implements
 * the 7-day refresh window described in the SDD:
 * - Login issues a 15-min access token + a 7-day refresh token
 * - Refresh token is exchanged for a new access token via POST /api/v1/auth/refresh
 * - Refresh tokens are single-use (revoked on use) and hashed (bcrypt)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash');        // bcrypt hash of the refresh token
            $table->timestamp('expires_at');
            $table->boolean('is_revoked')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->index(['user_id', 'is_revoked']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
