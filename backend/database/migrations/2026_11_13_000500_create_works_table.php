<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->string('thumbnail_path')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->text('description')->nullable();
            $table->string('body_part', 32)->nullable();
            $table->boolean('is_color')->nullable(); // null = unknown, false = black & grey
            $table->unsignedSmallInteger('position')->default(0);

            // SigLIP 2 base → 768 dims; e5-small → 384 dims (Phase 2)
            $table->vector('image_embedding', dimensions: 768)->nullable();
            $table->vector('caption_embedding', dimensions: 384)->nullable();

            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();

            $table->index(['artist_id', 'position']);
        });

        Schema::create('work_style', function (Blueprint $table) {
            $table->foreignId('work_id')->constrained()->cascadeOnDelete();
            $table->foreignId('style_id')->constrained()->cascadeOnDelete();
            $table->float('weight')->default(1);
            $table->string('source', 8)->default('artist'); // artist | ai
            $table->timestamps();
            $table->primary(['work_id', 'style_id']);
            $table->index('style_id');
        });

        DB::statement('ALTER TABLE work_style ADD CONSTRAINT work_style_weight_check CHECK (weight >= 0 AND weight <= 1)');
        DB::statement("ALTER TABLE work_style ADD CONSTRAINT work_style_source_check CHECK (source IN ('artist', 'ai'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('work_style');
        Schema::dropIfExists('works');
    }
};
