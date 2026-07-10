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
        Schema::create('project_contributors', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('project_id');
            $table->foreign('project_id', 'fk_pc_project')
                ->references('id')->on('research_projects')
                ->onDelete('cascade');

            $table->string('member_uimp_id', 100);
            $table->string('contributor_role', 100)->nullable();

            $table->index(['project_id', 'member_uimp_id'], 'idx_pc_project_member');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_contributors');
    }
};