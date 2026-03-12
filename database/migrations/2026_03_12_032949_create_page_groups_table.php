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
        Schema::create('page_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('site_id')->constrained()->cascadeOnDelete();
            $table->string('group_key');
            $table->string('label');
            $table->string('pattern_type');
            $table->json('match_rules');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['site_id', 'group_key']);
            $table->index(['site_id', 'group_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_groups');
    }
};
