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
        Schema::create('research_equipment', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('research_group_id');
            $table->foreign('research_group_id', 'fk_re_group')
                ->references('id')->on('research_groups')
                ->onDelete('restrict');

            $table->string('asset_name', 300);
            $table->string('category', 150);
            $table->string('manufacturer', 200);
            $table->string('model_number', 150);

            // Unique globally (SDD §4.5.3 — not a partial-index case).
            $table->string('serial_number', 150)->unique('uq_re_serial_number');

            $table->date('purchase_date');
            $table->decimal('acquisition_cost', 15, 2);
            $table->decimal('replacement_value', 15, 2)->nullable();
            $table->unsignedSmallInteger('estimated_useful_life_years')->nullable();
            $table->string('laboratory_ref_id', 100);
            $table->enum('status', [
                'Available', 'Booked', 'Under-Maintenance', 'Decommissioned', 'In-Transit',
            ])->default('Available');

            // Standard RGMS audit columns (SDD §4.1.2)
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->uuid('updated_by');
            $table->timestamp('deleted_at')->nullable();
            $table->uuid('deleted_by')->nullable();

            $table->index(['research_group_id', 'status'], 'idx_re_group_status');
            $table->index('laboratory_ref_id', 'idx_re_lab');
            $table->index('category', 'idx_re_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('research_equipment');
    }
};