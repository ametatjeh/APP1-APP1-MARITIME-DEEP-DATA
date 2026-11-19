<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('iso_code_2', 2)->unique();
            $table->string('iso_code_3', 3)->unique();
            $table->string('numeric_code', 3)->nullable();
            $table->string('name')->index();
            $table->string('official_name')->nullable();
            $table->string('flag_icon_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->jsonb('metadata')->nullable(); // capital, EEZ area km2, UNCLOS ratification date, etc.
            $table->timestampsTz();
        });

        // Add foreign key constraint from users table to countries table
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('country_restriction_id')->references('id')->on('countries')->nullOnDelete();
        });

        Schema::create('country_regulations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('country_id')->constrained('countries')->cascadeOnDelete();
            $table->string('title');
            $table->string('regulation_number')->nullable()->index();
            $table->string('issuing_authority')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('repeal_date')->nullable();
            $table->string('status')->default('active'); // active, repealed, draft, amended
            $table->text('summary')->nullable();
            $table->foreignUuid('source_record_id')->nullable()->constrained('source_records')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('country_datasets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('country_id')->constrained('countries')->cascadeOnDelete();
            $table->string('dataset_name');
            $table->string('dataset_type'); // vessel_registry, fishing_licenses, maritime_boundaries, penalties
            $table->foreignUuid('data_source_id')->nullable()->constrained('data_sources')->nullOnDelete();
            $table->string('version')->default('1.0');
            $table->timestampTz('last_updated_at')->nullable();
            $table->integer('records_count')->default(0);
            $table->string('status')->default('active'); // active, archived, deprecated
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['country_restriction_id']);
        });
        Schema::dropIfExists('country_datasets');
        Schema::dropIfExists('country_regulations');
        Schema::dropIfExists('countries');
    }
};
