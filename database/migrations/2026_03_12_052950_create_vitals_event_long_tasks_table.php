<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vitals_event_long_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vitals_event_id')->constrained('vitals_events')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->text('script_url')->nullable();
            $table->string('script_host')->nullable();
            $table->string('invoker_type')->nullable();
            $table->string('container_selector')->nullable();
            $table->decimal('start_time_ms', 12, 3)->nullable();
            $table->decimal('duration_ms', 12, 3);
            $table->decimal('blocking_duration_ms', 12, 3)->nullable();
            $table->timestamps();

            $table->index(['vitals_event_id', 'duration_ms'], 'vitals_event_long_tasks_event_duration_idx');
            $table->index(['script_host', 'invoker_type'], 'vitals_event_long_tasks_host_invoker_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vitals_event_long_tasks');
    }
};
