<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vitals_events', function (Blueprint $table) {
            $table->uuid('source_event_id')->nullable()->after('id');
            $table->string('correlation_id')->nullable()->after('page_view_id');
            $table->string('trace_id')->nullable()->after('correlation_id');
            $table->json('context')->nullable()->after('tags');

            $table->unique(['site_id', 'source_event_id'], 'vitals_events_site_source_event_unique');
            $table->index(['site_id', 'correlation_id'], 'vitals_events_correlation_idx');
            $table->index(['site_id', 'trace_id'], 'vitals_events_trace_idx');
        });
    }

    public function down(): void
    {
        Schema::table('vitals_events', function (Blueprint $table) {
            $table->dropUnique('vitals_events_site_source_event_unique');
            $table->dropIndex('vitals_events_correlation_idx');
            $table->dropIndex('vitals_events_trace_idx');
            $table->dropColumn([
                'source_event_id',
                'correlation_id',
                'trace_id',
                'context',
            ]);
        });
    }
};
