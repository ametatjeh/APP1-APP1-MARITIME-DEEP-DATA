<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('verifiable_type')->index();
            $table->uuid('verifiable_id')->index();
            $table->foreignUuid('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('previous_status');
            $table->string('new_status'); // EvidenceVerificationStatus
            $table->string('decision');
            $table->text('written_conclusion'); // Mandatory analyst rationale
            $table->foreignUuid('supporting_evidence_id')->nullable()->constrained('evidences')->nullOnDelete();
            $table->timestampTz('reviewed_at')->useCurrent();
            $table->timestampsTz();
        });

        Schema::create('chain_of_custodies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('evidence_id')->constrained('evidences')->cascadeOnDelete();
            $table->string('action'); // CustodyAction: collected, uploaded, hash_generated, etc.
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('actor_role');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('hash_at_action', 64);
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['evidence_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chain_of_custodies');
        Schema::dropIfExists('verification_reviews');
    }
};
