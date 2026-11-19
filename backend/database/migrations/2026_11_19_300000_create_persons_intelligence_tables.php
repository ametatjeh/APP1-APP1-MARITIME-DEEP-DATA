<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name')->index();
            $table->string('primary_role')->default('other')->index(); // officer, director, beneficial_owner, skipper, crew, manning_agent, broker, target, other
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->foreignUuid('nationality_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('gender')->nullable(); // male, female, other, unknown
            
            // Encrypted PII fields for sensitive identity protection
            $table->text('encrypted_national_id')->nullable();
            $table->text('encrypted_passport_number')->nullable();
            $table->text('encrypted_seaman_book_number')->nullable();
            $table->text('encrypted_tax_id')->nullable();
            $table->string('masked_identifier')->nullable(); // e.g. ID-******1234 for safe UI display
            
            $table->boolean('is_pep')->default(false)->index(); // Politically Exposed Person
            $table->boolean('is_sanctioned')->default(false)->index();
            $table->jsonb('sanction_details')->nullable();
            
            $table->string('verification_status')->default('unverified')->index(); // unverified, pending, under_review, verified, flagged_suspicious, disputed
            $table->boolean('is_redacted')->default(false);
            
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->foreignUuid('data_source_id')->nullable()->constrained('data_sources')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        DB::statement('CREATE INDEX idx_persons_full_name_trgm ON persons USING GIN (full_name gin_trgm_ops)');

        Schema::create('person_aliases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained('persons')->cascadeOnDelete();
            $table->string('alias_name')->index();
            $table->string('alias_type')->default('spelling_variant'); // nickname, spelling_variant, transliteration, alias_used_in_contract, legal_former_name
            $table->string('notes')->nullable();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->timestampsTz();
        });

        DB::statement('CREATE INDEX idx_person_aliases_trgm ON person_aliases USING GIN (alias_name gin_trgm_ops)');

        Schema::create('person_identifiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained('persons')->cascadeOnDelete();
            $table->string('identifier_type')->index(); // passport, national_id, seaman_book, tax_id, driver_license
            $table->text('encrypted_value');
            $table->string('masked_value');
            $table->foreignUuid('issuing_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_current')->default(true)->index();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('person_corporate_ties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained('persons')->cascadeOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('role')->index(); // director, officer, shareholder, beneficial_owner, legal_representative
            $table->decimal('shares_percentage', 5, 2)->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_current')->default(true)->index();
            $table->string('verification_status')->default('unverified')->index();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('person_vessel_ties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained('persons')->cascadeOnDelete();
            $table->foreignUuid('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->string('role')->index(); // skipper_master, owner_operator, charterer, chief_engineer, beneficial_owner, crew_member
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('license_number')->nullable();
            $table->boolean('is_current')->default(true)->index();
            $table->string('verification_status')->default('unverified')->index();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_vessel_ties');
        Schema::dropIfExists('person_corporate_ties');
        Schema::dropIfExists('person_identifiers');
        Schema::dropIfExists('person_aliases');
        Schema::dropIfExists('persons');
    }
};
