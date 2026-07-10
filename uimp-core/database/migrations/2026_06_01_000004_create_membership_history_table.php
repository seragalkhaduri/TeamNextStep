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
        Schema::create('membership_history', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('membership_id');
            $table->foreign('membership_id', 'fk_mh_membership')
                ->references('id')->on('group_memberships')
                ->onDelete('restrict');

            $table->string('previous_role', 50)->nullable();
            $table->string('new_role', 50)->nullable();
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();
            $table->string('change_reason', 500)->nullable();
            $table->string('changed_by', 100);
            $table->timestamp('changed_at')->useCurrent();

            $table->index(['membership_id', 'changed_at'], 'idx_mh_membership');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_history');
    }
};