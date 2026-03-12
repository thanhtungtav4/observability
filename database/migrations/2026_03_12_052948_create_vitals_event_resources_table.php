<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vitals_event_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vitals_event_id')->constrained('vitals_events')->cascadeOnDelete();
            $table->text('resource_url');
            $table->string('resource_host')->nullable();
            $table->text('resource_path')->nullable();
            $table->string('resource_type')->nullable();
            $table->string('initiator_type')->nullable();
            $table->decimal('duration_ms', 12, 3)->nullable();
            $table->unsignedBigInteger('transfer_size')->nullable();
            $table->unsignedBigInteger('decoded_body_size')->nullable();
            $table->string('cache_state')->nullable();
            $table->string('priority')->nullable();
            $table->boolean('render_blocking')->default(false);
            $table->boolean('is_lcp_candidate')->default(false);
            $table->timestamps();

            $table->index(['vitals_event_id', 'is_lcp_candidate'], 'vitals_event_resources_lcp_idx');
            $table->index(['resource_host', 'resource_type'], 'vitals_event_resources_host_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vitals_event_resources');
    }
};
