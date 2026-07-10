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
        Schema::create('funding_sources', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('research_group_id');
            $table->foreign('research_group_id', 'fk_fs_group')
                ->references('id')->on('research_groups')
                ->onDelete('restrict');

            $table->string('agency_name', 300);

            // Unique globally (SDD §4.5.3 — not a partial-index case,
            // grant references must never be reused even after deletion).
            $table->string('grant_reference', 150)->unique('uq_fs_grant_reference');

            $table->decimal('allocated_amount', 15, 2);
            $table->char('currency_code', 3)->default('LYD');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['Active', 'Expired', 'Exhausted', 'Suspended'])
                ->default('Active');
            $table->text('notes')->nullable();

            // Standard RGMS audit columns (SDD §4.1.2)
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->uuid('updated_by');
            $table->timestamp('deleted_at')->nullable();
            $table->uuid('deleted_by')->nullable();

            $table->index(['research_group_id', 'status'], 'idx_fs_group_status');
            $table->index('end_date', 'idx_fs_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funding_sources');
    }
};