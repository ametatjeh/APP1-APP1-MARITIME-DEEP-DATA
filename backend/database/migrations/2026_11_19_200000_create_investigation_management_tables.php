<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigation_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('case_number')->unique(); // e.g. MDD-INV-2026-0001
            $table->string('title')->index();
            $table->text('summary')->nullable();
            $table->string('category')->index(); // CaseCategory
            $table->string('priority')->default('medium')->index(); // CasePriority
            $table->string('status')->default('draft')->index(); // CaseStatus
            $table->string('confidentiality_level')->default('standard')->index(); // CaseConfidentiality
            $table->foreignUuid('lead_investigator_id')->constrained('users')->restrictOnDelete();
            $table->date('incident_date_from')->nullable();
            $table->date('incident_date_to')->nullable();
            $table->text('findings_summary')->nullable();
            $table->jsonb('tags')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('case_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('investigation_cases')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_in_case')->default('analyst'); // CaseRole
            $table->timestampTz('assigned_at')->useCurrent();
            $table->timestampTz('unassigned_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignUuid('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['case_id', 'user_id', 'role_in_case']);
        });

        Schema::create('case_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('investigation_cases')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('note_text');
            $table->string('note_type')->default('analyst_assessment'); // hypothesis, witness_report, field_observation, analyst_assessment
            $table->boolean('is_confidential')->default(false);
            $table->timestampsTz();
        });

        Schema::create('case_timelines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('investigation_cases')->cascadeOnDelete();
            $table->timestampTz('event_date')->index();
            $table->string('event_title');
            $table->text('event_description')->nullable();
            $table->string('event_type')->default('incident'); // incident, detection, port_call, transfer, license_revocation, enforcement_action
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('case_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('investigation_cases')->cascadeOnDelete();
            $table->string('subject_type')->index(); // vessel, company, person, location, other
            $table->uuid('subject_id')->nullable()->index(); // ID of vessel, company, etc. if existing
            $table->string('subject_name')->index();
            $table->string('subject_role')->index(); // SubjectRole
            $table->text('involvement_summary')->nullable();
            $table->date('date_identified')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_subjects');
        Schema::dropIfExists('case_timelines');
        Schema::dropIfExists('case_notes');
        Schema::dropIfExists('case_assignments');
        Schema::dropIfExists('investigation_cases');
    }
};
