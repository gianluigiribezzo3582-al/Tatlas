<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artists', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // stage name
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('website')->nullable();
            $table->jsonb('languages')->default('[]'); // ISO 639-1 codes
            $table->boolean('accepts_walk_in')->default(false);
            $table->unsignedTinyInteger('price_range')->nullable(); // 1 (€) .. 3 (€€€)

            $table->foreignId('claimed_by_user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            // multilingual-e5-small → 384 dimensions (Phase 2)
            $table->vector('text_embedding', dimensions: 384)->nullable();

            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE artists ADD CONSTRAINT artists_price_range_check CHECK (price_range BETWEEN 1 AND 3)');

        Schema::create('artist_studio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('studio_id')->constrained()->cascadeOnDelete();
            $table->string('type', 8); // resident | guest
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();

            $table->index(['studio_id', 'type', 'starts_at']);
            $table->index(['artist_id', 'type']);
        });

        DB::statement("ALTER TABLE artist_studio ADD CONSTRAINT artist_studio_type_check CHECK (type IN ('resident', 'guest'))");
        DB::statement("ALTER TABLE artist_studio ADD CONSTRAINT artist_studio_guest_dates_check CHECK (type <> 'guest' OR starts_at IS NOT NULL)");
        DB::statement('ALTER TABLE artist_studio ADD CONSTRAINT artist_studio_date_order_check CHECK (ends_at IS NULL OR starts_at IS NULL OR ends_at >= starts_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('artist_studio');
        Schema::dropIfExists('artists');
    }
};
