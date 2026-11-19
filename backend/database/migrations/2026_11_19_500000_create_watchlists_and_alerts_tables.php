<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Watchlists
        Schema::create('watchlists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->default('custom'); // iuu_high_risk, forced_labour_risk, sanctions_monitoring, identity_fraud, flags_of_convenience, custom
            $table->string('priority')->default('medium'); // critical, high, medium, low
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'priority', 'is_active']);
        });

        // 2. Watchlist Items (Polymorphic-friendly entity reference)
        Schema::create('watchlist_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('watchlist_id')->constrained('watchlists')->cascadeOnDelete();
            $table->string('entity_type'); // vessel, company, person
            $table->uuid('entity_id');
            $table->text('reason')->nullable();
            $table->foreignUuid('evidence_id')->nullable()->constrained('evidences')->nullOnDelete();
            $table->foreignUuid('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['entity_type', 'entity_id', 'is_active']);
        });

        // 3. Analyst Alerts
        Schema::create('analyst_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('alert_reference')->unique(); // ALT-YYYY-XXXX
            $table->foreignUuid('watchlist_id')->nullable()->constrained('watchlists')->nullOnDelete();
            $table->foreignUuid('watchlist_item_id')->nullable()->constrained('watchlist_items')->nullOnDelete();
            $table->string('entity_type'); // vessel, company, person
            $table->uuid('entity_id');
            $table->string('alert_type'); // iuu_listing_detected, identity_conflict_detected, forced_labour_flag, flag_change, import_quarantine, watchlist_match
            $table->string('severity')->default('medium'); // critical, high, medium, low
            $table->string('title');
            $table->text('details')->nullable();
            $table->jsonb('context_data')->nullable();
            $table->string('status')->default('active'); // active, acknowledged, resolved, dismissed
            $table->foreignUuid('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('acknowledged_at')->nullable();
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity', 'alert_type']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyst_alerts');
        Schema::dropIfExists('watchlist_items');
        Schema::dropIfExists('watchlists');
    }
};
