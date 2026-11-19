<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiInvestigationFinding;
use App\Services\AiAssistantService;
use App\Services\AiVectorSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function __construct(
        protected AiAssistantService $aiService,
        protected AiVectorSearchService $vectorService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $conversations = AiConversation::where('user_id', $request->user()->id)
            ->withCount(['messages', 'findings'])
            ->orderByDesc('updated_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($conversations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'context_type' => 'nullable|string|in:vessel,company,person,case,general',
            'context_id' => 'nullable|uuid',
            'agent_mode' => 'nullable|string|in:orchestrator,vessel,corporate,iuu,crew,jurisdiction',
            'metadata' => 'nullable|array',
        ]);

        $conversation = $this->aiService->createConversation($validated, $request->user());

        return response()->json([
            'data' => $conversation,
            'message' => 'AI Conversation initialized successfully.',
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $conversation = AiConversation::with([
            'messages' => fn($q) => $q->orderBy('created_at', 'asc'),
            'findings.reviewer:id,name',
        ])
        ->where('user_id', $request->user()->id)
        ->findOrFail($id);

        return response()->json(['data' => $conversation]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $conversation = AiConversation::where('user_id', $request->user()->id)->findOrFail($id);
        $conversation->delete();

        return response()->json(['message' => 'Conversation deleted successfully.']);
    }

    public function query(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string|min:2|max:4000',
        ]);

        $conversation = AiConversation::where('user_id', $request->user()->id)->findOrFail($id);

        $result = $this->aiService->queryConversation($conversation, $request->input('prompt'), $request->user());

        return response()->json([
            'data' => $result,
            'message' => 'Multi-agent analysis completed.',
        ]);
    }

    public function reviewFinding(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'is_verified' => 'required|boolean',
            'notes' => 'required|string|min:5|max:2000',
        ]);

        $finding = AiInvestigationFinding::findOrFail($id);

        $reviewed = $this->aiService->reviewFinding(
            $finding,
            $request->boolean('is_verified'),
            $request->input('notes'),
            $request->user()
        );

        return response()->json([
            'data' => $reviewed,
            'message' => 'Analyst review logged successfully.',
        ]);
    }

    public function agents(): JsonResponse
    {
        return response()->json([
            'orchestrator' => 'OrchestratorAgent',
            'specialist_agents' => [
                ['name' => 'VesselIntelligenceAgent', 'domain' => 'vessel_master', 'description' => 'Evaluates vessel particulars, FoC flags, and dimensions.'],
                ['name' => 'IdentityResolutionAgent', 'domain' => 'identity_resolution', 'description' => 'Audits identifier collisions, duplicate MMSIs, and flag-hopping.'],
                ['name' => 'CorporateIntelligenceAgent', 'domain' => 'corporate_networks', 'description' => 'Examines beneficial ownership, offshore secrecy, and holding webs.'],
                ['name' => 'CrewResearchAgent', 'domain' => 'labour_rights', 'description' => 'Inspects ILO 11 forced labour indicators and skipper track records.'],
                ['name' => 'MaritimeJurisdictionAgent', 'domain' => 'maritime_boundaries', 'description' => 'Analyzes EEZ baselines, Natuna waters, and disputed borders.'],
                ['name' => 'IuuResearchAgent', 'domain' => 'iuu_sanctions', 'description' => 'Checks RFMO blacklists and unlicensed fishing allegations.'],
                ['name' => 'EvidenceAnalysisAgent', 'domain' => 'evidence_verification', 'description' => 'Audits cryptographic SHA-256 integrity and custody chains.'],
                ['name' => 'ResearchAssistantAgent', 'domain' => 'synthesis_and_reporting', 'description' => 'Formulates evidence-grounded reports and analyst next steps.'],
            ],
        ]);
    }

    public function vectorSync(): JsonResponse
    {
        $result = $this->vectorService->syncEntityEmbeddings();

        return response()->json([
            'data' => $result,
            'message' => 'Master entities synced to vector embeddings successfully.',
        ]);
    }

    public function vectorSearch(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'top_k' => 'nullable|integer|min:1|max:20',
            'entity_type' => 'nullable|string|in:vessel,company,person,case,evidence,iuu_listing',
        ]);

        $results = $this->vectorService->search(
            $request->input('query'),
            $request->input('top_k', 5),
            $request->input('entity_type')
        );

        return response()->json([
            'data' => $results,
            'total' => count($results),
        ]);
    }
}
