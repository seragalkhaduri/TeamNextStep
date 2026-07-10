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
        Schema::create('publication_authors', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('publication_id');
            $table->foreign('publication_id', 'fk_pa_publication')
                ->references('id')->on('publications')
                ->onDelete('cascade');

            $table->string('member_uimp_id', 100);
            $table->unsignedTinyInteger('author_order');
            $table->string('contribution_type', 100)->nullable();

            $table->index(['publication_id', 'author_order'], 'idx_pa_publication_order');
            $table->index('member_uimp_id', 'idx_pa_member');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publication_authors');
    }
};