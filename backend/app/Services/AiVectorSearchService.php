<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EntityEmbedding;
use App\Models\Evidence;
use App\Models\InvestigationCase;
use App\Models\IuuVesselListing;
use App\Models\Person;
use App\Models\Vessel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AiVectorSearchService
{
    protected string $aiServiceUrl;
    protected ?string $aiServiceToken;

    public function __construct()
    {
        $this->aiServiceUrl = config('services.ai.url', 'http://127.0.0.1:8100');
        $this->aiServiceToken = config('services.ai.token');
    }

    /**
     * Compute 64-dimensional normalized embedding vector.
     * Uses Python FastAPI service if available, otherwise executes identical deterministic feature hashing.
     */
    public function generateEmbedding(string $text, int $dimension = 64): array
    {
        try {
            $req = Http::connectTimeout(0.15)->timeout(0.5);
            if (!empty($this->aiServiceToken)) {
                $req = $req->withHeaders(['X-Internal-Token' => $this->aiServiceToken]);
            }
            $response = $req->post("{$this->aiServiceUrl}/api/v1/embed", [
                'text' => $text,
                'dimension' => $dimension,
            ]);

            if ($response->successful()) {
                return $response->json('embedding', []);
            }
        } catch (\Throwable $e) {
            // Fallback to internal deterministic vector hashing
        }

        return $this->deterministicEmbedding($text, $dimension);
    }

    public function deterministicEmbedding(string $text, int $dimension = 64): array
    {
        $stopwords = [
            'in', 'on', 'the', 'a', 'an', 'and', 'for', 'to', 'of', 'under',
            'between', 'is', 'at', 'by', 'as', 'with', 'from', 'or', 'meeting',
            'that', 'this', 'it', 'be', 'are', 'was', 'were'
        ];
        $vector = array_fill(0, $dimension, 0.0);
        preg_match_all('/\b\w+\b/u', mb_strtolower($text), $matches);
        $rawTokens = $matches[0] ?? [];
        $tokens = array_values(array_filter($rawTokens, fn($t) => !in_array($t, $stopwords)));

        if (empty($tokens)) {
            return $vector;
        }

        // Unigrams & Character trigrams
        foreach ($tokens as $token) {
            $hash = hexdec(substr(hash('sha256', $token), 0, 8));
            $idx = $hash % $dimension;
            $weight = 1.0 + (strlen($token) * 0.15);
            $vector[$idx] += $weight;

            $len = strlen($token);
            for ($i = 0; $i < $len - 2; $i++) {
                $sub = substr($token, $i, 3);
                $hs = hexdec(substr(hash('sha256', $sub), 0, 8));
                $vector[$hs % $dimension] += 0.35;
            }
        }

        // Bigrams
        $count = count($tokens);
        for ($i = 0; $i < $count - 1; $i++) {
            $bigram = $tokens[$i] . '_' . $tokens[$i + 1];
            $hash = hexdec(substr(hash('sha256', $bigram), 0, 8));
            $idx = $hash % $dimension;
            $vector[$idx] += 2.2;
        }

        // L2 Normalization
        $sumSq = 0.0;
        foreach ($vector as $v) {
            $sumSq += $v * $v;
        }
        $norm = sqrt($sumSq);
        if ($norm > 0) {
            foreach ($vector as $k => $v) {
                $vector[$k] = round($v / $norm, 6);
            }
        }

        return $vector;
    }

    /**
     * Sync entities to vector embeddings table.
     */
    public function syncEntityEmbeddings(): array
    {
        $synced = 0;

        // 1. Vessels
        $vessels = Vessel::with('currentFlagCountry')->limit(50)->get();
        foreach ($vessels as $v) {
            $vTitle = $v->current_name ?? 'Vessel Record';
            $flagCode = $v->currentFlagCountry->iso_alpha2 ?? '';
            $text = "Vessel: {$vTitle} IMO: {$v->imo_number} MMSI: {$v->current_mmsi} Callsign: {$v->current_callsign} Flag: {$flagCode} Type: {$v->vessel_type}";
            $embedding = $this->generateEmbedding($text);
            EntityEmbedding::updateOrCreate(
                ['entity_type' => 'vessel', 'entity_id' => $v->id],
                [
                    'chunk_title' => $vTitle,
                    'content_chunk' => $text,
                    'embedding' => $embedding,
                    'dimension' => 64,
                    'model_name' => 'maritime-vector-v1',
                    'metadata' => ['flag' => $flagCode, 'imo' => $v->imo_number],
                ]
            );
            $synced++;
        }

        // 2. Companies
        $companies = Company::with('countryOfIncorporation')->limit(50)->get();
        foreach ($companies as $c) {
            $cTitle = $c->company_name ?? 'Company Record';
            $jur = $c->countryOfIncorporation->iso_alpha2 ?? '';
            $text = "Company: {$cTitle} Jurisdiction: {$jur} Legal Form: {$c->legal_form} Registration: {$c->registration_number}";
            $embedding = $this->generateEmbedding($text);
            EntityEmbedding::updateOrCreate(
                ['entity_type' => 'company', 'entity_id' => $c->id],
                [
                    'chunk_title' => $cTitle,
                    'content_chunk' => $text,
                    'embedding' => $embedding,
                    'dimension' => 64,
                    'model_name' => 'maritime-vector-v1',
                    'metadata' => ['jurisdiction' => $jur],
                ]
            );
            $synced++;
        }

        // 3. Evidences
        $evidences = Evidence::limit(50)->get();
        foreach ($evidences as $e) {
            $classStr = is_object($e->classification) ? ($e->classification->value ?? '') : (string) $e->classification;
            $statusStr = is_object($e->verification_status) ? ($e->verification_status->value ?? '') : (string) $e->verification_status;
            $text = "Evidence: {$e->title} SHA256: {$e->sha256_hash} Classification: {$classStr} Status: {$statusStr}";
            $embedding = $this->generateEmbedding($text);
            EntityEmbedding::updateOrCreate(
                ['entity_type' => 'evidence', 'entity_id' => $e->id],
                [
                    'chunk_title' => $e->title,
                    'content_chunk' => $text,
                    'embedding' => $embedding,
                    'dimension' => 64,
                    'model_name' => 'maritime-vector-v1',
                    'metadata' => ['sha256' => $e->sha256_hash, 'status' => $statusStr],
                ]
            );
            $synced++;
        }

        // 4. IUU Listings
        $iuuListings = IuuVesselListing::limit(50)->get();
        foreach ($iuuListings as $iuu) {
            $text = "IUU Listing: {$iuu->vessel_name} Org: {$iuu->listing_organization} Reason: {$iuu->listing_reason} Flag: {$iuu->reported_flag}";
            $embedding = $this->generateEmbedding($text);
            EntityEmbedding::updateOrCreate(
                ['entity_type' => 'iuu_listing', 'entity_id' => $iuu->id],
                [
                    'chunk_title' => "IUU: {$iuu->vessel_name}",
                    'content_chunk' => $text,
                    'embedding' => $embedding,
                    'dimension' => 64,
                    'model_name' => 'maritime-vector-v1',
                    'metadata' => ['org' => $iuu->listing_organization],
                ]
            );
            $synced++;
        }

        return ['synced_records' => $synced];
    }

    /**
     * Search vector database via Cosine Similarity.
     */
    public function search(string $query, int $topK = 5, ?string $entityType = null): array
    {
        $queryEmbedding = $this->generateEmbedding($query);
        $queryJson = json_encode($queryEmbedding);

        $dbQuery = EntityEmbedding::query();

        if ($entityType) {
            $dbQuery->where('entity_type', $entityType);
        }

        // Use PostgreSQL function maritime_cosine_similarity_json
        $results = $dbQuery
            ->selectRaw('*, maritime_cosine_similarity_json(embedding, ?::jsonb) as similarity', [$queryJson])
            ->whereRaw('maritime_cosine_similarity_json(embedding, ?::jsonb) > 0.01', [$queryJson])
            ->orderByDesc('similarity')
            ->limit($topK)
            ->get();

        return $results->map(function ($item) {
            return [
                'id' => $item->id,
                'entity_type' => $item->entity_type,
                'entity_id' => $item->entity_id,
                'title' => $item->chunk_title,
                'content' => $item->content_chunk,
                'similarity' => round((float) $item->similarity, 4),
                'metadata' => $item->metadata,
            ];
        })->toArray();
    }
}
