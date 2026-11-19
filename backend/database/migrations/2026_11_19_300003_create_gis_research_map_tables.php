<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_polygons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->index();
            $table->string('category')->default('investigation_aoi')->index(); // high_risk_iuu_zone, transshipment_corridor, patrol_area, marine_protected_area, investigation_aoi, disputed_claim_aoi
            $table->text('description')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();
        });

        // Add PostGIS spatial geometry column for research polygons
        DB::statement('ALTER TABLE research_polygons ADD COLUMN geom geometry(MultiPolygon, 4326)');
        DB::statement('CREATE INDEX idx_research_polygons_geom ON research_polygons USING GIST (geom)');

        Schema::create('spatial_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title')->index();
            $table->string('event_type')->index(); // inspection_point, vessel_seizure, port_call, transshipment_rendezvous, reported_sighting, abandoned_crew_location
            $table->timestampTz('event_date')->index();
            $table->foreignUuid('vessel_id')->nullable()->constrained('vessels')->nullOnDelete();
            $table->foreignUuid('investigation_case_id')->nullable()->constrained('investigation_cases')->nullOnDelete();
            $table->foreignUuid('maritime_jurisdiction_id')->nullable()->constrained('maritime_jurisdictions')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();
        });

        // Add PostGIS spatial point for spatial events
        DB::statement('ALTER TABLE spatial_events ADD COLUMN geom geometry(Point, 4326)');
        DB::statement('CREATE INDEX idx_spatial_events_geom ON spatial_events USING GIST (geom)');
    }

    public function down(): void
    {
        Schema::dropIfExists('spatial_events');
        Schema::dropIfExists('research_polygons');
    }
};
