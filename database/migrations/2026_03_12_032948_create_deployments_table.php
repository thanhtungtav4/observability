<?php

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
        Schema::create('deployments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('site_id')->constrained()->cascadeOnDelete();
            $table->string('environment');
            $table->string('build_id');
            $table->string('release_version')->nullable();
            $table->string('git_ref')->nullable();
            $table->string('git_commit_sha')->nullable();
            $table->timestamp('deployed_at');
            $table->string('actor_name')->nullable();
            $table->string('ci_source')->nullable();
            $table->json('metadata');
            $table->timestamps();

            $table->unique(['site_id', 'environment', 'build_id']);
            $table->index(['site_id', 'environment', 'deployed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
