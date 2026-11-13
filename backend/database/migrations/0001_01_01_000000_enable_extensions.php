<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::ensureExtensionExists('postgis');
        Schema::ensureVectorExtensionExists();
    }

    public function down(): void
    {
        // Extensions are shared infrastructure: never dropped by a rollback.
    }
};
