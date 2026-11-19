<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->nullable()->constrained('investigation_cases')->nullOnDelete();
            $table->string('title')->index();
            $table->string('evidence_type')->index(); // EvidenceType
            $table->string('classification')->default('confidential')->index(); // EvidenceClassification
            $table->text('file_path');
            $table->string('file_name');
            $table->string('file_extension', 10);
            $table->string('mime_type');
            $table->bigInteger('file_size_bytes');
            $table->string('sha256_hash', 64)->index();
            $table->foreignUuid('data_source_id')->nullable()->constrained('data_sources')->nullOnDelete();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->text('source_url')->nullable();
            $table->date('collection_date')->nullable();
            $table->date('publication_date')->nullable();
            $table->string('verification_status')->default('pending_verification')->index(); // EvidenceVerificationStatus
            $table->foreignUuid('collected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('evidence_relationships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('evidence_id')->constrained('evidences')->cascadeOnDelete();
            $table->string('target_type')->index(); // vessel, company, case_subject, case, evidence
            $table->uuid('target_id')->index();
            $table->string('relationship_type'); // proves_ownership, shows_transshipment, confirms_identity, documents_violation, supporting_reference
            $table->text('notes')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_relationships');
        Schema::dropIfExists('evidences');
    }
};
