<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\WatchlistCategory;
use App\Enums\WatchlistPriority;
use App\Http\Controllers\Controller;
use App\Models\AnalystAlert;
use App\Models\Watchlist;
use App\Models\WatchlistItem;
use App\Services\WatchlistAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WatchlistController extends Controller
{
    public function __construct(
        protected WatchlistAlertService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Watchlist::with(['creator:id,name'])->withCount(['items', 'alerts']);

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        $watchlists = $query->orderByDesc('created_at')->paginate($request->input('per_page', 25));

        return response()->json($watchlists);
    }

    public function show(string $id): JsonResponse
    {
        $watchlist = Watchlist::with([
            'creator:id,name',
            'items' => function ($q) {
                $q->where('is_active', true)->with('addedByUser:id,name', 'evidence:id,title');
            },
            'alerts' => function ($q) {
                $q->latest()->limit(15);
            },
        ])
        ->withCount(['items', 'alerts'])
        ->findOrFail($id);

        // Append resolved entities
        $itemsWithEntities = $watchlist->items->map(function ($item) {
            $data = $item->toArray();
            $data['entity'] = $item->entity;
            return $data;
        });

        $res = $watchlist->toArray();
        $res['items'] = $itemsWithEntities;

        return response()->json(['data' => $res]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => ['nullable', Rule::enum(WatchlistCategory::class)],
            'priority' => ['nullable', Rule::enum(WatchlistPriority::class)],
            'is_active' => 'nullable|boolean',
        ]);

        $watchlist = $this->service->createWatchlist($validated, $request->user());

        return response()->json([
            'data' => $watchlist,
            'message' => 'Monitoring watchlist created successfully.',
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $watchlist = Watchlist::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category' => ['nullable', Rule::enum(WatchlistCategory::class)],
            'priority' => ['nullable', Rule::enum(WatchlistPriority::class)],
            'is_active' => 'nullable|boolean',
        ]);

        $watchlist->update($validated);

        return response()->json([
            'data' => $watchlist,
            'message' => 'Watchlist updated successfully.',
        ]);
    }

    public function addItem(Request $request, string $id): JsonResponse
    {
        $watchlist = Watchlist::findOrFail($id);

        $validated = $request->validate([
            'entity_type' => 'required|string|in:vessel,company,person',
            'entity_id' => 'required|uuid',
            'reason' => 'nullable|string',
            'evidence_id' => 'nullable|exists:evidences,id',
        ]);

        $item = $this->service->addItem(
            watchlist: $watchlist,
            entityType: $validated['entity_type'],
            entityId: $validated['entity_id'],
            reason: $validated['reason'] ?? null,
            evidenceId: $validated['evidence_id'] ?? null,
            user: $request->user()
        );

        return response()->json([
            'data' => $item,
            'message' => 'Entity added to watchlist.',
        ], 201);
    }

    public function removeItem(string $id, string $itemId): JsonResponse
    {
        $watchlist = Watchlist::findOrFail($id);
        $item = $watchlist->items()->findOrFail($itemId);

        $item->update(['is_active' => false]);

        return response()->json([
            'message' => 'Entity removed from watchlist.',
        ]);
    }

    // Alerts Feed
    public function alerts(Request $request): JsonResponse
    {
        $query = AnalystAlert::with(['watchlist:id,name', 'watchlistItem', 'acknowledgedByUser:id,name', 'resolvedByUser:id,name']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($severity = $request->input('severity')) {
            $query->where('severity', $severity);
        }

        if ($type = $request->input('alert_type')) {
            $query->where('alert_type', $type);
        }

        $alerts = $query->orderByDesc('created_at')->paginate($request->input('per_page', 25));

        // Append resolved entities
        $alerts->getCollection()->transform(function ($alert) {
            $alert->entity = $alert->entity;
            return $alert;
        });

        return response()->json($alerts);
    }

    public function acknowledgeAlert(Request $request, string $id): JsonResponse
    {
        $alert = AnalystAlert::findOrFail($id);
        $acknowledged = $this->service->acknowledgeAlert($alert, $request->user());

        return response()->json([
            'data' => $acknowledged,
            'message' => 'Alert acknowledged.',
        ]);
    }

    public function resolveAlert(Request $request, string $id): JsonResponse
    {
        $alert = AnalystAlert::findOrFail($id);

        $validated = $request->validate([
            'notes' => 'required|string',
        ]);

        $resolved = $this->service->resolveAlert($alert, $validated['notes'], $request->user());

        return response()->json([
            'data' => $resolved,
            'message' => 'Alert resolved.',
        ]);
    }
}
