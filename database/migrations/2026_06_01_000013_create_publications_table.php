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
        Schema::create('publications', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('research_group_id');
            $table->foreign('research_group_id', 'fk_pub_group')
                ->references('id')->on('research_groups')
                ->onDelete('restrict');

            $table->string('title', 500);
            $table->enum('publication_type', [
                'Journal-Article', 'Conference-Paper', 'Book-Chapter', 'Technical-Report',
            ]);
            $table->smallInteger('publication_year');
            $table->enum('status', [
                'In-Preparation', 'Submitted', 'Under-Review', 'Accepted', 'Published', 'Retracted',
            ])->default('In-Preparation');
            $table->string('doi', 255)->nullable();
            $table->string('journal_name', 300)->nullable();
            $table->string('conference_name', 300)->nullable();
            $table->string('issn', 20)->nullable();
            $table->string('publisher', 300)->nullable();
            $table->decimal('impact_factor', 8, 3)->nullable();
            $table->unsignedInteger('citation_count')->default(0);
            $table->timestamp('citation_updated_at')->nullable();

            // Standard RGMS audit columns (SDD §4.1.2)
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->uuid('updated_by');
            $table->timestamp('deleted_at')->nullable();
            $table->uuid('deleted_by')->nullable();

            // MySQL has no native partial index (SDD §4.5.3: "doi unique
            // among active records ... AND doi IS NOT NULL"). Stored
            // generated column mirrors doi only while active and non-null;
            // NULL is excluded from the UNIQUE index automatically.
            $table->string('doi_active_key', 255)
                ->nullable()
                ->storedAs('IF(deleted_at IS NULL AND doi IS NOT NULL, doi, NULL)');
        });

        Schema::table('publications', function (Blueprint $table): void {
            $table->unique('doi_active_key', 'uq_pub_doi_active');

            $table->index(['research_group_id', 'status'], 'idx_pub_group_status');
            $table->index('publication_year', 'idx_pub_year');
            $table->index('publication_type', 'idx_pub_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};