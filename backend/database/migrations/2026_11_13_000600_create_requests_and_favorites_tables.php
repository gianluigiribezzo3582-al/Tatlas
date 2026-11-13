<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A user asks to manage an existing studio or artist profile.
        Schema::create('claim_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('claimable'); // Studio | Artist
            $table->string('status', 16)->default('pending'); // pending | approved | rejected
            $table->text('message')->nullable();
            $table->string('evidence_url')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        // GDPR: anyone (even without an account) can ask for a listing to be removed.
        Schema::create('removal_requests', function (Blueprint $table) {
            $table->id();
            $table->string('requester_name');
            $table->string('requester_email');
            $table->nullableMorphs('subject'); // Studio | Artist | Work
            $table->text('reason');
            $table->string('status', 16)->default('pending'); // pending | done | rejected
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        // Phase 3 feature; table defined now to keep the model complete.
        Schema::create('favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'artist_id']);
        });

        DB::statement("ALTER TABLE claim_requests ADD CONSTRAINT claim_requests_status_check CHECK (status IN ('pending', 'approved', 'rejected'))");
        DB::statement("ALTER TABLE removal_requests ADD CONSTRAINT removal_requests_status_check CHECK (status IN ('pending', 'done', 'rejected'))");
        // At most one pending claim per user and target.
        DB::statement("CREATE UNIQUE INDEX claim_requests_one_pending ON claim_requests (user_id, claimable_type, claimable_id) WHERE status = 'pending'");
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('removal_requests');
        Schema::dropIfExists('claim_requests');
    }
};
