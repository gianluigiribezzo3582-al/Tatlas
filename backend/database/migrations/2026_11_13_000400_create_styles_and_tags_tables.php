<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Controlled, hierarchical taxonomy.
        Schema::create('styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('styles')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('name_it');
            $table->string('name_en');
            $table->jsonb('synonyms')->default('[]'); // lowercase IT/EN variants used for matching
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('artist_style', function (Blueprint $table) {
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('style_id')->constrained()->cascadeOnDelete();
            $table->float('weight')->default(1);
            $table->string('source', 8)->default('artist'); // artist | ai
            $table->timestamps();
            $table->primary(['artist_id', 'style_id']);
            $table->index('style_id');
        });

        // Free tags, normalized (slug is the canonical lowercase form).
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');
            $table->primary(['tag_id', 'taggable_type', 'taggable_id']);
        });

        DB::statement('ALTER TABLE artist_style ADD CONSTRAINT artist_style_weight_check CHECK (weight >= 0 AND weight <= 1)');
        DB::statement("ALTER TABLE artist_style ADD CONSTRAINT artist_style_source_check CHECK (source IN ('artist', 'ai'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('artist_style');
        Schema::dropIfExists('styles');
    }
};
