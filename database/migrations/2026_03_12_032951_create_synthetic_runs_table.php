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
        Schema::create('synthetic_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('site_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('deployment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('page_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('page_group_key');
            $table->string('environment');
            $table->timestamp('occurred_at');
            $table->string('build_id');
            $table->string('release_version')->nullable();
            $table->string('git_ref')->nullable();
            $table->string('git_commit_sha')->nullable();
            $table->string('runner')->default('lighthouse');
            $table->string('device_preset');
            $table->text('page_url');
            $table->text('page_path');
            $table->decimal('performance_score', 5, 2);
            $table->decimal('accessibility_score', 5, 2)->nullable();
            $table->decimal('best_practices_score', 5, 2)->nullable();
            $table->decimal('seo_score', 5, 2)->nullable();
            $table->integer('fcp_ms')->nullable();
            $table->integer('lcp_ms')->nullable();
            $table->integer('tbt_ms')->nullable();
            $table->decimal('cls_score', 8, 3)->nullable();
            $table->integer('speed_index_ms')->nullable();
            $table->integer('inp_ms')->nullable();
            $table->json('opportunities');
            $table->json('diagnostics');
            $table->text('screenshot_url')->nullable();
            $table->text('trace_url')->nullable();
            $table->text('report_url')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'environment', 'occurred_at'], 'synthetic_runs_site_env_occurred_at_idx');
            $table->index(['page_group_key', 'device_preset', 'occurred_at'], 'synthetic_runs_page_group_idx');
            $table->index(['site_id', 'environment', 'build_id', 'occurred_at'], 'synthetic_runs_build_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('synthetic_runs');
    }
};
