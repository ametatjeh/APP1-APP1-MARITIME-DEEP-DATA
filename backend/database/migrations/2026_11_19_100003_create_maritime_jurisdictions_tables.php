<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maritime_jurisdictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('country_id')->constrained('countries')->restrictOnDelete();
            $table->string('jurisdiction_type'); 
            // internal_waters, archipelagic_waters, territorial_sea, contiguous_zone, eez, continental_shelf, extended_continental_shelf, disputed_area, joint_development_zone
            $table->string('area_name');
            $table->text('legal_basis')->nullable(); // treaty, decree, UNCLOS declaration
            $table->string('legal_status')->default('agreed'); // agreed, claimed, disputed, unilateral, provisional
            $table->string('ratification_status')->nullable();
            $table->date('effective_date')->nullable();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->string('verification_status')->default('verified'); // verified, unverified, disputed
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();
        });

        // Add PostGIS Geometry column and spatial GiST index
        DB::statement('ALTER TABLE maritime_jurisdictions ADD COLUMN geom geometry(MultiPolygon, 4326)');
        DB::statement('CREATE INDEX idx_maritime_jurisdictions_geom ON maritime_jurisdictions USING GIST (geom)');
        DB::statement('CREATE INDEX idx_maritime_jurisdictions_type_status ON maritime_jurisdictions (jurisdiction_type, legal_status)');

        Schema::create('maritime_boundary_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('claimant_country_id')->constrained('countries')->restrictOnDelete();
            $table->foreignUuid('opposing_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('dispute_name');
            $table->string('boundary_type');
            $table->text('claim_description')->nullable();
            $table->text('legal_basis_claimant')->nullable();
            $table->text('legal_basis_opposing')->nullable();
            $table->string('status')->default('active_dispute'); // active_dispute, negotiation, adjudicated_icj_itlos, dormant
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE maritime_boundary_claims ADD COLUMN geom geometry(MultiPolygon, 4326)');
        DB::statement('CREATE INDEX idx_maritime_boundary_claims_geom ON maritime_boundary_claims USING GIST (geom)');

        Schema::create('overlapping_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('jurisdiction_a_id')->constrained('maritime_jurisdictions')->cascadeOnDelete();
            $table->foreignUuid('jurisdiction_b_id')->constrained('maritime_jurisdictions')->cascadeOnDelete();
            $table->string('claim_type'); // overlapping_eez, disputed_continental_shelf, joint_development
            $table->string('resolution_status')->default('unresolved'); // unresolved, bilateral_talks, joint_patrol_zone, icj_pending
            $table->text('analyst_notes')->nullable();
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE overlapping_claims ADD COLUMN overlap_geom geometry(MultiPolygon, 4326)');
        DB::statement('CREATE INDEX idx_overlapping_claims_geom ON overlapping_claims USING GIST (overlap_geom)');
    }

    public function down(): void
    {
        Schema::dropIfExists('overlapping_claims');
        Schema::dropIfExists('maritime_boundary_claims');
        Schema::dropIfExists('maritime_jurisdictions');
    }
};
