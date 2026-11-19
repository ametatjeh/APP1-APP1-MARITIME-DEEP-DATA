<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vessel_identity_conflicts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vessel_a_id')->constrained('vessels')->cascadeOnDelete();
            $table->foreignUuid('vessel_b_id')->constrained('vessels')->cascadeOnDelete();
            $table->string('conflicting_identifier_type'); // mmsi, imo, callsign, national_registration
            $table->string('conflicting_identifier_value')->index();
            $table->string('conflict_nature'); // shared_mmsi, cloned_imo, recycled_callsign, dual_flag_registration
            $table->decimal('conflict_score', 5, 2)->default(0.00); // 0.00 to 100.00
            $table->string('resolution_status')->default('unreviewed')->index(); 
            // unreviewed, under_review, confirmed_distinct_vessels, confirmed_same_vessel_pending_merge, dismissed_noise
            $table->foreignUuid('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('analyst_conclusion')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('vessel_aliases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->string('alias_name')->index();
            $table->string('alias_type'); // spelling_variant, transliteration, former_name, intelligence_codename
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->decimal('confidence_score', 3, 2)->default(1.00);
            $table->timestampsTz();
        });

        DB::statement('CREATE INDEX idx_vessel_aliases_name_trgm ON vessel_aliases USING GIN (alias_name gin_trgm_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_aliases');
        Schema::dropIfExists('vessel_identity_conflicts');
    }
};
