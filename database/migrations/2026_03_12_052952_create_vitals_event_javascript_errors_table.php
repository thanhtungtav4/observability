<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vitals_event_javascript_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vitals_event_id')->constrained('vitals_events')->cascadeOnDelete();
            $table->string('fingerprint');
            $table->string('name')->nullable();
            $table->text('message');
            $table->text('source_url')->nullable();
            $table->string('source_host')->nullable();
            $table->unsignedInteger('line_number')->nullable();
            $table->unsignedInteger('column_number')->nullable();
            $table->boolean('handled')->default(false);
            $table->longText('stack')->nullable();
            $table->timestamps();

            $table->index(['vitals_event_id', 'fingerprint'], 'vitals_event_errors_event_fingerprint_idx');
            $table->index(['source_host', 'handled'], 'vitals_event_errors_host_handled_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vitals_event_javascript_errors');
    }
};
