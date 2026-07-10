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
        Schema::create('equipment_maintenance', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('equipment_id');
            $table->foreign('equipment_id', 'fk_em_equipment')
                ->references('id')->on('research_equipment')
                ->onDelete('restrict');

            $table->enum('maintenance_type', ['Preventive', 'Corrective']);
            $table->date('scheduled_date');
            $table->date('completion_date')->nullable();
            $table->string('performed_by', 200);
            $table->text('notes')->nullable();

            // Audit columns per SDD §4.2.14 CREATE TABLE — no soft-delete
            // columns on this table (permanent maintenance log).
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->uuid('updated_by');

            $table->index(['equipment_id', 'scheduled_date'], 'idx_em_equipment_date');
            $table->index('maintenance_type', 'idx_em_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_maintenance');
    }
};