<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('batch_reference')->unique(); // e.g. IMP-2026-0001
            $table->foreignUuid('data_source_id')->constrained('data_sources')->cascadeOnDelete();
            $table->foreignUuid('dataset_version_id')->nullable()->constrained('dataset_versions')->nullOnDelete();
            $table->foreignUuid('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('source_filename');
            $table->string('file_format', 20)->default('csv'); // csv, json, geojson, xlsx
            $table->bigInteger('file_size_bytes')->default(0);
            $table->string('file_sha256', 64)->index();
            $table->string('target_entity_type')->default('vessel')->index(); // vessel, company, person, iuu_listing, spatial_event
            $table->string('status')->default('pending')->index(); // pending, validating, importing, completed, failed, rolled_back, partially_imported
            
            // Metrics & counters
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('valid_rows_count')->default(0);
            $table->integer('quarantined_rows_count')->default(0);
            $table->integer('duplicate_rows_count')->default(0);
            
            $table->jsonb('validation_rules_applied')->nullable();
            $table->jsonb('summary_report')->nullable();
            
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('import_quarantined_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('data_import_id')->constrained('data_imports')->cascadeOnDelete();
            $table->integer('row_index')->index();
            $table->jsonb('raw_payload');
            $table->string('quarantine_reason')->index(); // invalid_imo_checksum, invalid_mmsi_length, missing_required_fields, duplicate_record, type_mismatch, schema_error
            $table->text('error_details');
            $table->string('resolution_status')->default('pending_review')->index(); // pending_review, corrected_and_reprocessed, discarded, whitelisted
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_quarantined_records');
        Schema::dropIfExists('data_imports');
    }
};
