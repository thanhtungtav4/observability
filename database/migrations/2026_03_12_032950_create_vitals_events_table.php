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
        Schema::create('vitals_events', function (Blueprint $table) {
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
            $table->string('metric_name');
            $table->string('metric_unit');
            $table->decimal('metric_value', 12, 3);
            $table->decimal('delta_value', 12, 3)->nullable();
            $table->string('rating');
            $table->text('url');
            $table->text('path');
            $table->string('page_title')->nullable();
            $table->string('device_class');
            $table->string('navigation_type')->nullable();
            $table->string('browser_name')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('os_name')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('effective_connection_type')->nullable();
            $table->integer('round_trip_time_ms')->nullable();
            $table->decimal('downlink_mbps', 8, 2)->nullable();
            $table->uuid('session_id')->nullable();
            $table->uuid('page_view_id')->nullable();
            $table->string('visitor_hash')->nullable();
            $table->json('attribution');
            $table->json('tags');
            $table->timestamps();

            $table->index(['site_id', 'environment', 'metric_name', 'device_class', 'occurred_at'], 'vitals_events_rollup_idx');
            $table->index(['page_group_key', 'metric_name', 'device_class', 'occurred_at'], 'vitals_events_page_group_idx');
            $table->index(['deployment_id', 'metric_name', 'device_class', 'occurred_at'], 'vitals_events_deployment_idx');
            $table->index(['site_id', 'environment', 'build_id', 'metric_name', 'device_class', 'occurred_at'], 'vitals_events_build_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vitals_events');
    }
};
