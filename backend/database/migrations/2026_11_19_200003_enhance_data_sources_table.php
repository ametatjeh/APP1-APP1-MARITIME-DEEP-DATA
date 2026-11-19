<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_sources', function (Blueprint $table) {
            $table->string('category')->default('government')->index(); // SourceCategory
            $table->string('publisher')->nullable();
            $table->date('publication_date')->nullable();
            $table->foreignUuid('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('verification_status')->default('verified')->index();
        });
    }

    public function down(): void
    {
        Schema::table('data_sources', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropColumn([
                'category',
                'publisher',
                'publication_date',
                'country_id',
                'verification_status',
            ]);
        });
    }
};
