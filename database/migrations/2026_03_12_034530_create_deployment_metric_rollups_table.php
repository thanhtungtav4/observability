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
        Schema::create('deployment_metric_rollups', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('deployment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('site_id')->constrained()->cascadeOnDelete();
            $table->string('environment');
            $table->string('page_group_key');
            $table->string('build_id');
            $table->string('release_version')->nullable();
            $table->string('metric_name');
            $table->string('metric_unit');
            $table->string('device_class');
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->unsignedInteger('sample_count');
            $table->decimal('p50_value', 12, 3);
            $table->decimal('p75_value', 12, 3);
            $table->unsignedInteger('good_count');
            $table->unsignedInteger('needs_improvement_count');
            $table->unsignedInteger('poor_count');
            $table->timestamps();

            $table->index(['site_id', 'environment', 'deployment_id'], 'deployment_metric_rollups_site_env_dep_idx');
            $table->index(['deployment_id', 'metric_name', 'device_class'], 'deployment_metric_rollups_metric_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployment_metric_rollups');
    }
};
