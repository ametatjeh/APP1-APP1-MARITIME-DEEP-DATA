<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Research & Dossier Reports
        Schema::create('research_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('report_reference')->unique(); // REP-YYYY-XXXX
            $table->string('title');
            $table->string('report_type'); // vessel_dossier, corporate_network_investigation, case_summary, iuu_infraction_brief, forced_labour_assessment
            $table->string('classification')->default('restricted'); // restricted, confidential, public_summary
            $table->string('target_entity_type')->nullable(); // vessel, company, person, case
            $table->uuid('target_entity_id')->nullable();
            $table->text('executive_summary')->nullable();
            $table->longText('body_markdown')->nullable();
            $table->jsonb('structured_payload')->nullable(); // Snapshot of compiled vessel/company/case details
            $table->string('sha256_checksum', 64)->nullable();
            $table->foreignUuid('investigation_case_id')->nullable()->constrained('investigation_cases')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('published_at')->nullable();
            $table->timestamps();

            $table->index(['report_type', 'classification']);
            $table->index(['target_entity_type', 'target_entity_id']);
        });

        // 2. Report Citations (Strict Evidence & Source Provenance Linking)
        Schema::create('report_citations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('research_report_id')->constrained('research_reports')->cascadeOnDelete();
            $table->foreignUuid('evidence_id')->nullable()->constrained('evidences')->nullOnDelete();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->string('citation_key'); // e.g. [CIT-1]
            $table->text('statement_supported');
            $table->timestamps();

            $table->index(['research_report_id', 'citation_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_citations');
        Schema::dropIfExists('research_reports');
    }
};
