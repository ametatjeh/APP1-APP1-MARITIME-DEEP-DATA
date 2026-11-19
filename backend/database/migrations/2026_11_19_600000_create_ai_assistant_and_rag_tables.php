<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. PL/pgSQL function for JSONB vector cosine similarity
        DB::statement('
            CREATE OR REPLACE FUNCTION maritime_cosine_similarity_json(a JSONB, b JSONB)
            RETURNS DOUBLE PRECISION AS $$
            DECLARE
                dot_product DOUBLE PRECISION := 0.0;
                norm_a DOUBLE PRECISION := 0.0;
                norm_b DOUBLE PRECISION := 0.0;
                len INTEGER;
                val_a DOUBLE PRECISION;
                val_b DOUBLE PRECISION;
            BEGIN
                len := jsonb_array_length(a);
                IF len IS NULL OR len != jsonb_array_length(b) OR len = 0 THEN
                    RETURN 0.0;
                END IF;
                FOR i IN 0..(len - 1) LOOP
                    val_a := (a->>i)::DOUBLE PRECISION;
                    val_b := (b->>i)::DOUBLE PRECISION;
                    dot_product := dot_product + (val_a * val_b);
                    norm_a := norm_a + (val_a * val_a);
                    norm_b := norm_b + (val_b * val_b);
                END LOOP;
                IF norm_a = 0.0 OR norm_b = 0.0 THEN
                    RETURN 0.0;
                END IF;
                RETURN dot_product / (sqrt(norm_a) * sqrt(norm_b));
            END;
            $$ LANGUAGE plpgsql IMMUTABLE;
        ');

        // 2. AI Conversations Table
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('context_type')->default('general'); // vessel, company, person, case, general
            $table->uuid('context_id')->nullable();
            $table->string('agent_mode')->default('orchestrator'); // orchestrator, vessel, corporate, iuu, crew, jurisdiction, evidence
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['context_type', 'context_id']);
        });

        // 3. AI Messages Table
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->string('role'); // user, assistant, system, tool
            $table->string('agent_name')->nullable(); // OrchestratorAgent, VesselIntelligenceAgent, etc.
            $table->text('content');
            $table->jsonb('citations')->nullable(); // [{evidence_id, source_id, quotation, title, confidence}]
            $table->jsonb('reasoning_trace')->nullable(); // {steps, sub_agents_consulted, tools_executed}
            $table->integer('tokens_used')->default(0);
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });

        // 4. Entity Vector Embeddings Table
        Schema::create('entity_embeddings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type'); // vessel, company, person, case, evidence, iuu_listing
            $table->uuid('entity_id');
            $table->string('chunk_title');
            $table->text('content_chunk');
            $table->jsonb('embedding'); // array of floats
            $table->integer('dimension')->default(64);
            $table->string('model_name')->default('maritime-vector-v1');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });

        // 5. AI Investigation Findings (Subject to Analyst Verification)
        Schema::create('ai_investigation_findings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->foreignUuid('message_id')->nullable()->constrained('ai_messages')->nullOnDelete();
            $table->string('finding_type'); // potential_identity_fraud, beneficial_ownership_obscurity, forced_labour_risk, iuu_fishing_suspicion, jurisdiction_violation
            $table->string('title');
            $table->text('summary');
            $table->jsonb('evidence_citations')->nullable();
            $table->string('confidence_level')->default('medium'); // high, medium, low
            $table->boolean('is_verified_by_analyst')->default(false);
            $table->text('analyst_review_notes')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'is_verified_by_analyst']);
            $table->index('finding_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_investigation_findings');
        Schema::dropIfExists('entity_embeddings');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        DB::statement('DROP FUNCTION IF EXISTS maritime_cosine_similarity_json(JSONB, JSONB);');
    }
};
