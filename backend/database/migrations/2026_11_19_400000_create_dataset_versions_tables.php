<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dataset_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('data_source_id')->constrained('data_sources')->cascadeOnDelete();
            $table->foreignUuid('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('dataset_name')->index();
            $table->string('version_tag')->index(); // v1.0, v2.1, 2026-Q1
            $table->date('publication_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('deprecation_date')->nullable();
            $table->boolean('is_current')->default(true)->index();
            $table->string('snapshot_sha256', 64)->nullable()->index();
            $table->integer('records_count')->default(0);
            $table->jsonb('schema_definition')->nullable(); // Expected headers, data types, constraints
            $table->text('changelog')->nullable();
            $table->string('status')->default('active')->index(); // draft, active, deprecated, superseded
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dataset_versions');
    }
};
