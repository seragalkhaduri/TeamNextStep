<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Users table (SDD §4.2).
 *
 * UUID primary keys, dual-language not needed here (users don't have name_en/ar),
 * but preferred_language drives the bilingual experience.
 *
 * Security fields: failed_login_count, locked_until (SDD §6.2 lockout policy).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('preferred_language', 2)->default('ar'); // enum ar/en, default ar (SDD §4.5)
            $table->boolean('is_active')->default(true);
            $table->smallInteger('failed_login_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('password_reset_token')->nullable();
            $table->timestamp('password_reset_expires')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Unique partial indexes where deleted_at IS NULL (SDD §4.3)
        // Laravel's schema builder doesn't support partial indexes natively on Postgres,
        // so we use raw SQL.
        if (config('database.default') === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement(
                'CREATE UNIQUE INDEX users_username_unique_active ON users (username) WHERE deleted_at IS NULL'
            );
            \Illuminate\Support\Facades\DB::statement(
                'CREATE UNIQUE INDEX users_email_unique_active ON users (email) WHERE deleted_at IS NULL'
            );
            // Drop the default unique indexes that don't account for soft deletes
            \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS users_username_unique');
            \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS users_email_unique');
        }

        // Keep the default sessions & password_reset_tokens tables for Laravel internals
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
