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
        Schema::create('daily_metric_rollups', function (Blueprint $table) {
            $table->id();
            $table->date('metric_day');
            $table->foreignUuid('site_id')->constrained()->cascadeOnDelete();
            $table->string('environment');
            $table->string('page_group_key');
            $table->foreignUuid('deployment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('build_id');
            $table->string('metric_name');
            $table->string('metric_unit');
            $table->string('device_class');
            $table->unsignedInteger('sample_count');
            $table->decimal('p50_value', 12, 3);
            $table->decimal('p75_value', 12, 3);
            $table->unsignedInteger('good_count');
            $table->unsignedInteger('needs_improvement_count');
            $table->unsignedInteger('poor_count');
            $table->timestamps();

            $table->index(['metric_day', 'site_id', 'environment'], 'daily_metric_rollups_day_site_env_idx');
            $table->index(['site_id', 'metric_name', 'device_class'], 'daily_metric_rollups_site_metric_idx');
            $table->index(['deployment_id', 'metric_name', 'device_class'], 'daily_metric_rollups_deployment_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_metric_rollups');
    }
};
