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
        Schema::create('group_status_history', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('group_id');
            $table->foreign('group_id', 'fk_gsh_group')
                ->references('id')->on('research_groups')
                ->onDelete('restrict');

            $table->string('old_status', 50);
            $table->string('new_status', 50);
            $table->text('justification')->nullable();
            $table->string('transitioned_by', 100);
            $table->timestamp('transitioned_at')->useCurrent();

            $table->index(['group_id', 'transitioned_at'], 'idx_gsh_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_status_history');
    }
};