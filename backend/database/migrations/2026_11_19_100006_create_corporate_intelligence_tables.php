<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_name')->index();
            $table->string('registration_number')->nullable()->index();
            $table->foreignUuid('country_of_incorporation_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->date('incorporation_date')->nullable();
            $table->date('dissolution_date')->nullable();
            $table->string('legal_form')->nullable(); // LLC, PT, Ltd, Corp, Offshore Shell
            $table->string('corporate_status')->default('active')->index(); // active, dissolved, suspended, dormant, struck_off
            $table->text('registered_address')->nullable();
            $table->boolean('is_sanctioned')->default(false)->index();
            $table->jsonb('sanction_details')->nullable();
            $table->string('verification_status')->default('unverified')->index();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        DB::statement('CREATE INDEX idx_companies_name_trgm ON companies USING GIN (company_name gin_trgm_ops)');

        Schema::create('company_identifiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('identifier_type')->index(); // national_tax_id, registry_code, lei_code, duns
            $table->string('identifier_value')->index();
            $table->foreignUuid('issuing_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->boolean('is_current')->default(true)->index();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('company_officers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('officer_name')->index();
            $table->string('position'); // director, president, commissioner, secretary, general_manager
            $table->date('appointment_date')->nullable();
            $table->date('resignation_date')->nullable();
            $table->boolean('is_current')->default(true)->index();
            $table->boolean('is_redacted')->default(false);
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('company_shareholders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('shareholder_name')->index();
            $table->string('shareholder_type')->default('corporate'); // individual, corporate, trust, state
            $table->decimal('share_percentage', 5, 2)->nullable();
            $table->bigInteger('shares_count')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_current')->default(true)->index();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('corporate_relationships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('child_company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('relationship_type'); // parent_subsidiary, beneficial_owner, holding_structure, joint_venture, shell_intermediate
            $table->decimal('ownership_percentage', 5, 2)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_current')->default(true)->index();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->string('verification_status')->default('unverified');
            $table->timestampsTz();
        });

        Schema::create('vessel_company_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('assignment_type')->index(); // registered_owner, beneficial_owner, commercial_manager, technical_manager, operator, charterer
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_current')->default(true)->index();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->string('verification_status')->default('unverified');
            $table->text('notes')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_company_assignments');
        Schema::dropIfExists('corporate_relationships');
        Schema::dropIfExists('company_shareholders');
        Schema::dropIfExists('company_officers');
        Schema::dropIfExists('company_identifiers');
        Schema::dropIfExists('companies');
    }
};
