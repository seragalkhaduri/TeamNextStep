<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Facilities tables: campuses, buildings, rooms (SDD §4.2).
 * Indexes per SDD §4.3.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── Campuses ────────────────────────────────────────────────
        Schema::create('campuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_en');
            $table->string('name_ar');
            $table->text('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Buildings ───────────────────────────────────────────────
        Schema::create('buildings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('code')->unique();
            $table->foreignUuid('campus_id')->constrained('campuses');
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Rooms ───────────────────────────────────────────────────
        Schema::create('rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('room_type');       // RoomType enum
            $table->integer('capacity')->nullable();
            $table->string('availability_status')->default('AVAILABLE'); // AvailabilityStatus enum
            $table->foreignUuid('building_id')->constrained('buildings');
            $table->timestamps();
            $table->softDeletes();

            // Indexes per SDD §4.3
            $table->index('building_id');
        });

        // Composite index on (room_type, availability_status) per SDD §4.3
        if (config('database.default') === 'pgsql') {
            DB::statement('CREATE INDEX rooms_type_status ON rooms (room_type, availability_status)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('buildings');
        Schema::dropIfExists('campuses');
    }
};
