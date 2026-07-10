<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the fk_rg_funding constraint on research_groups.funding_source_id
     * (SDD §4.2.1), deferred from the original create-table migration to
     * resolve the circular FK dependency with funding_sources
     * (SDD §4.1.1: funding_sources.research_group_id -> research_groups.id).
     */
    public function up(): void
    {
        Schema::table('research_groups', function (Blueprint $table): void {
            $table->foreign('funding_source_id', 'fk_rg_funding')
                ->references('id')->on('funding_sources')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('research_groups', function (Blueprint $table): void {
            $table->dropForeign('fk_rg_funding');
        });
    }
};