<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('equipment_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('equipment_id');
            $table->foreign('equipment_id', 'fk_ea_equipment')
                ->references('id')->on('research_equipment')
                ->onDelete('restrict');

            $table->string('requester_uimp_id', 100);
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('purpose', 500);
            $table->enum('status', ['Confirmed', 'Cancelled', 'Completed'])
                ->default('Confirmed');
            $table->text('requester_notes')->nullable();
            $table->text('cancellation_reason')->nullable();

            // No soft-delete — booking history is permanent (SDD §3.9.7).
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->uuid('updated_by');

            // Critical performance index (SDD §4.6): supports an
            // index-only range scan for conflict detection —
            // WHERE equipment_id = ? AND status = 'Confirmed'
            // AND start_datetime < [end] AND end_datetime > [start].
            $table->index(
                ['equipment_id', 'status', 'start_datetime', 'end_datetime'],
                'idx_ea_conflict_detection'
            );
            $table->index('requester_uimp_id', 'idx_ea_requester');
        });

        // CHECK constraint (SDD §4.5.2).
        DB::statement(
            'ALTER TABLE equipment_assignments '
            . 'ADD CONSTRAINT chk_ea_dates CHECK (end_datetime > start_datetime)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_assignments');
    }
};