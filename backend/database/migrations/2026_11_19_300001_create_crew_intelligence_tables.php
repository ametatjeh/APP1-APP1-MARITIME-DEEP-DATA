<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manning_agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('agent_name')->index();
            $table->string('license_number')->nullable()->index();
            $table->foreignUuid('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->text('registered_address')->nullable();
            $table->string('compliance_rating')->default('unrated')->index(); // compliant, under_scrutiny, high_risk_flagged, suspended_revoked, unrated
            $table->text('past_violations_summary')->nullable();
            $table->string('verification_status')->default('unverified')->index();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->timestampsTz();
        });

        DB::statement('CREATE INDEX idx_manning_agents_name_trgm ON manning_agents USING GIN (agent_name gin_trgm_ops)');

        Schema::create('crew_employments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained('persons')->cascadeOnDelete();
            $table->foreignUuid('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->foreignUuid('manning_agent_id')->nullable()->constrained('manning_agents')->nullOnDelete();
            $table->string('role_on_board')->index(); // master_skipper, chief_engineer, second_engineer, bosun, deckhand, cook, fisheries_observer, trainee, other
            $table->date('embarkation_date')->nullable();
            $table->string('embarkation_port')->nullable();
            $table->date('disembarkation_date')->nullable();
            $table->string('disembarkation_port')->nullable();
            $table->string('contract_status')->default('completed')->index(); // completed, active_on_board, abandoned, repatriated, deceased, escaped_reported, disputed
            $table->string('salary_currency', 3)->default('USD');
            $table->decimal('reported_monthly_wage', 10, 2)->nullable();
            $table->boolean('is_forced_labour_suspected')->default(false)->index();
            $table->string('verification_status')->default('unverified')->index();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestampsTz();
        });

        Schema::create('labour_violation_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('crew_employment_id')->nullable()->constrained('crew_employments')->cascadeOnDelete();
            $table->foreignUuid('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->foreignUuid('person_id')->nullable()->constrained('persons')->cascadeOnDelete();
            $table->string('indicator_type')->index(); // ILO 11 indicators: abuse_of_vulnerability, deception, restriction_of_movement, isolation_transshipment, violence_intimidation, retention_of_documents, withholding_of_wages, debt_bondage, abusive_conditions, excessive_overtime, other
            $table->string('severity')->default('medium')->index(); // low, medium, high, critical
            $table->date('incident_date')->nullable();
            $table->text('incident_description');
            $table->string('reported_by')->nullable();
            $table->string('reporting_organization')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('verification_status')->default('unverified')->index();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->foreignUuid('investigation_case_id')->nullable()->constrained('investigation_cases')->nullOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labour_violation_reports');
        Schema::dropIfExists('crew_employments');
        Schema::dropIfExists('manning_agents');
    }
};
