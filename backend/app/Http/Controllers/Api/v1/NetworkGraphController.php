<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\NetworkGraphService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NetworkGraphController extends Controller
{
    public function __construct(
        protected NetworkGraphService $service
    ) {}

    public function getGraph(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'seed_type' => 'nullable|string|in:vessel,company,person',
            'seed_id' => 'nullable|uuid',
            'depth' => 'nullable|integer|between:1,4',
        ]);

        $graph = $this->service->getGraphData(
            seedType: $validated['seed_type'] ?? null,
            seedId: $validated['seed_id'] ?? null,
            depth: $validated['depth'] ?? 2
        );

        return response()->json($graph);
    }
}
