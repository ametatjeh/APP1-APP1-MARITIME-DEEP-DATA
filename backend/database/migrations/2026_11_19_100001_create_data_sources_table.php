<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('organization_type'); // government, rfmo, ngo, commercial, academic, public
            $table->decimal('reliability_score', 3, 2)->default(1.00); // 0.00 to 1.00
            $table->text('source_url')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();
        });

        Schema::create('source_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('data_source_id')->constrained('data_sources')->restrictOnDelete();
            $table->string('record_identifier')->index(); // document/entry ID from external source
            $table->date('record_date')->nullable();
            $table->timestampTz('retrieval_date')->useCurrent();
            $table->jsonb('verbatim_payload');
            $table->string('sha256_hash', 64)->nullable()->index();
            $table->string('verification_status')->default('unverified'); // unverified, verified, disputed, superseded
            $table->text('analyst_notes')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_records');
        Schema::dropIfExists('data_sources');
    }
};
