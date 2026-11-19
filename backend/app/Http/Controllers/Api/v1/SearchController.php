<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\AdvancedSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        protected AdvancedSearchService $service
    ) {}

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'types' => 'nullable|array',
            'types.*' => 'string|in:vessel,company,person,case,evidence,iuu',
            'limit' => 'nullable|integer|between:1,100',
            'country_id' => 'nullable|exists:countries,id',
        ]);

        $results = $this->service->search($validated);

        return response()->json($results);
    }
}
