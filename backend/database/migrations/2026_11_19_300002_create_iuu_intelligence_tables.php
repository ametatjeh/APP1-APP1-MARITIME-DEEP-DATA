<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iuu_vessel_listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->string('list_name')->index(); // e.g. WCPFC IUU List, IOTC IUU Vessel List, INTERPOL Purple Notice, EU IUU Vessel List
            $table->string('listing_organization')->index(); // WCPFC, IOTC, INTERPOL, KKP, EU_COMMISSION, ICCAT, SPRFMO, CCAMLR
            $table->date('listing_date');
            $table->date('delisting_date')->nullable();
            $table->text('listing_reason');
            $table->string('listing_status')->default('listed')->index(); // listed, delisted, cross_listed, under_review, provisional
            $table->string('reported_flag_state')->nullable();
            $table->string('reported_owner')->nullable();
            $table->string('reported_master')->nullable();
            $table->string('official_reference_number')->nullable();
            $table->string('source_url')->nullable();
            $table->foreignUuid('data_source_id')->nullable()->constrained('data_sources')->nullOnDelete();
            $table->string('verification_status')->default('verified')->index();
            $table->timestampsTz();
        });

        Schema::create('iuu_infraction_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->string('infraction_type')->index(); // unlicensed_fishing, unauthorized_transshipment, fishing_in_closed_area, prohibited_gear, shark_finning, ais_spoofing_dark_vessel, flag_hopping, tampering_with_vms, quota_exceeded, human_rights_abuse, other
            $table->string('event_category')->default('official_allegation')->index(); // official_adjudication, official_allegation, ngo_investigation_finding, analytical_assessment, unverified_report
            $table->string('severity')->default('serious')->index(); // minor, moderate, serious, critical
            $table->date('event_date')->index();
            $table->string('location_text')->nullable();
            $table->foreignUuid('maritime_jurisdiction_id')->nullable()->constrained('maritime_jurisdictions')->nullOnDelete();
            $table->text('description');
            $table->string('reporting_agency')->nullable(); // e.g. PSDKP KKP, TNI AL, Coast Guard, Environmental Justice Foundation
            $table->text('action_taken')->nullable(); // Seized, fined, sunk, under trial
            $table->foreignUuid('investigation_case_id')->nullable()->constrained('investigation_cases')->nullOnDelete();
            $table->foreignUuid('data_source_id')->nullable()->constrained('data_sources')->nullOnDelete();
            $table->string('verification_status')->default('unverified')->index();
            $table->timestampsTz();
        });

        // Add PostGIS spatial geometry point for precise geospatial analytics
        DB::statement('ALTER TABLE iuu_infraction_events ADD COLUMN geom geometry(Point, 4326)');
        DB::statement('CREATE INDEX idx_iuu_infraction_events_geom ON iuu_infraction_events USING GIST (geom)');
    }

    public function down(): void
    {
        Schema::dropIfExists('iuu_infraction_events');
        Schema::dropIfExists('iuu_vessel_listings');
    }
};
