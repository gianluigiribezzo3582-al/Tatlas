<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studios', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();

            $table->geography('location', subtype: 'point', srid: 4326);
            $table->string('street')->nullable();
            $table->string('housenumber', 32)->nullable();
            $table->string('postcode', 16)->nullable();
            $table->string('city')->nullable();
            $table->char('country_code', 2)->nullable();

            $table->string('opening_hours')->nullable(); // raw OSM opening_hours syntax
            $table->string('phone', 64)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('instagram_url')->nullable();

            // Provenance. OSM data is ODbL: we keep the reference to credit and resync.
            $table->string('source', 8)->default('user'); // osm | user
            $table->string('osm_type', 8)->nullable();    // node | way | relation
            $table->unsignedBigInteger('osm_id')->nullable();
            $table->jsonb('osm_tags')->nullable();
            $table->timestamp('osm_synced_at')->nullable();
            $table->timestamp('osm_missing_since')->nullable();
            // Fields edited by owners/admins that an OSM resync must not overwrite.
            $table->jsonb('locked_fields')->default('[]');

            $table->timestamp('hidden_at')->nullable(); // moderation / removal requests
            $table->timestamps();

            $table->unique(['osm_type', 'osm_id']);
            $table->index('city');
        });

        DB::statement('CREATE INDEX studios_location_gist ON studios USING GIST (location)');
        DB::statement("ALTER TABLE studios ADD CONSTRAINT studios_source_check CHECK (source IN ('osm', 'user'))");
        DB::statement("ALTER TABLE studios ADD CONSTRAINT studios_osm_ref_check CHECK (source <> 'osm' OR (osm_type IS NOT NULL AND osm_id IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('studios');
    }
};
