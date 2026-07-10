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
        Schema::create('patent_inventors', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('patent_id');
            $table->foreign('patent_id', 'fk_pi_patent')
                ->references('id')->on('patents')
                ->onDelete('cascade');

            $table->string('member_uimp_id', 100);
            $table->unsignedTinyInteger('inventor_order');

            $table->index(['patent_id', 'inventor_order'], 'idx_pi_patent_order');
            $table->index('member_uimp_id', 'idx_pi_member');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patent_inventors');
    }
};