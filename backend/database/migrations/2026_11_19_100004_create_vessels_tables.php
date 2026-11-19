<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vessels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('current_name')->index();
            $table->foreignUuid('current_flag_country_id')->nullable()->constrained('countries')->nullOnDelete();
            
            // IMPORTANT: Never assume IMO/MMSI/Callsign are unique globally or permanently.
            // They can be reused, reassigned, spoofed or duplicated across disputed registers.
            $table->string('imo_number', 10)->nullable()->index();
            $table->string('current_mmsi', 9)->nullable()->index();
            $table->string('current_callsign', 20)->nullable()->index();
            $table->string('national_registration_number')->nullable()->index();
            
            $table->string('vessel_type')->nullable()->index(); // trawler, longliner, purse_seiner, carrier_reefer, etc.
            $table->string('fishing_gear_type')->nullable();
            $table->decimal('gross_tonnage', 10, 2)->nullable();
            $table->decimal('length_overall', 8, 2)->nullable();
            $table->decimal('breadth', 8, 2)->nullable();
            $table->decimal('draught', 8, 2)->nullable();
            $table->decimal('engine_power_kw', 10, 2)->nullable();
            $table->integer('built_year')->nullable();
            $table->string('shipyard')->nullable();
            $table->string('port_of_registry')->nullable();
            
            $table->string('verification_status')->default('unverified')->index(); // unverified, verified, disputed, flagged_suspicious
            $table->jsonb('structural_specs')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        // Add GIN trigram index for fast fuzzy searching on vessel name
        DB::statement('CREATE INDEX idx_vessels_current_name_trgm ON vessels USING GIN (current_name gin_trgm_ops)');

        Schema::create('vessel_identifiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->string('identifier_type')->index(); // imo, mmsi, callsign, national_registration, cfr, rfmo_record_id
            $table->string('identifier_value')->index();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_current')->default(true)->index();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->string('verification_status')->default('unverified');
            $table->text('notes')->nullable();
            $table->timestampsTz();
        });

        Schema::create('vessel_names', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->string('name')->index();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_current')->default(true)->index();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->string('verification_status')->default('unverified');
            $table->timestampsTz();
        });

        DB::statement('CREATE INDEX idx_vessel_names_name_trgm ON vessel_names USING GIN (name gin_trgm_ops)');

        Schema::create('vessel_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->foreignUuid('country_id')->constrained('countries')->restrictOnDelete();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_current')->default(true)->index();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->string('verification_status')->default('unverified');
            $table->timestampsTz();
        });

        Schema::create('vessel_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->foreignUuid('country_id')->constrained('countries')->restrictOnDelete();
            $table->string('registration_number')->index();
            $table->string('port_of_registry')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('active'); // active, expired, cancelled, suspended
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->string('verification_status')->default('unverified');
            $table->timestampsTz();
        });

        Schema::create('vessel_licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->foreignUuid('issuing_country_id')->constrained('countries')->restrictOnDelete();
            $table->string('license_number')->index();
            $table->string('license_type'); // SIPI, SIKPI, high_seas_permit, coastal_commercial, etc.
            $table->string('authorized_gear')->nullable();
            $table->string('target_species')->nullable();
            $table->jsonb('authorized_zones')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->string('status')->default('valid'); // valid, expired, revoked, suspended
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->string('verification_status')->default('unverified');
            $table->timestampsTz();
        });

        Schema::create('vessel_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->text('file_path');
            $table->text('thumbnail_path')->nullable();
            $table->string('sha256_hash', 64)->index();
            $table->bigInteger('file_size_bytes');
            $table->string('mime_type');
            $table->string('caption')->nullable();
            $table->date('photo_date')->nullable();
            $table->text('taken_at_location')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_photos');
        Schema::dropIfExists('vessel_licenses');
        Schema::dropIfExists('vessel_registrations');
        Schema::dropIfExists('vessel_flags');
        Schema::dropIfExists('vessel_names');
        Schema::dropIfExists('vessel_identifiers');
        Schema::dropIfExists('vessels');
    }
};
