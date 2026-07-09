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
        Schema::create('patents', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('research_group_id');
            $table->foreign('research_group_id', 'fk_pat_group')
                ->references('id')->on('research_groups')
                ->onDelete('restrict');

            $table->string('title', 500);
            $table->string('patent_number', 100)->nullable();
            $table->string('registration_authority', 200);
            $table->date('filing_date');
            $table->date('grant_date')->nullable();
            $table->enum('status', [
                'Filed', 'Under-Examination', 'Granted', 'Rejected', 'Expired',
            ])->default('Filed');

            // Standard RGMS audit columns (SDD §4.1.2)
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->uuid('updated_by');
            $table->timestamp('deleted_at')->nullable();
            $table->uuid('deleted_by')->nullable();

            // MySQL has no native partial index (SDD §4.5.3: "patent_number
            // unique among active records ... AND patent_number IS NOT
            // NULL"). Stored generated column reproduces the same effect.
            $table->string('patent_number_active_key', 100)
                ->nullable()
                ->storedAs('IF(deleted_at IS NULL AND patent_number IS NOT NULL, patent_number, NULL)');
        });

        Schema::table('patents', function (Blueprint $table): void {
            $table->unique('patent_number_active_key', 'uq_pat_number_active');

            $table->index(['research_group_id', 'status'], 'idx_pat_group_status');
            $table->index('filing_date', 'idx_pat_filing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patents');
    }
};