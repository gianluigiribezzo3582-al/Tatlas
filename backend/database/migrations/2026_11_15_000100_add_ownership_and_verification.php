<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            // Set when an admin approves a claim: the owner can then edit the studio.
            $table->foreignId('claimed_by_user_id')->nullable()->after('locked_fields')->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->after('claimed_by_user_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('artists', function (Blueprint $table) {
            $table->index(['is_verified', 'hidden_at']);
        });
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->dropIndex(['is_verified', 'hidden_at']);
        });
        Schema::table('studios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropConstrainedForeignId('claimed_by_user_id');
        });
    }
};
