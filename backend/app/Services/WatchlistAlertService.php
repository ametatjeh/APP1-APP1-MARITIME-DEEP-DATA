<?php

namespace App\Services;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\AnalystAlert;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Person;
use App\Models\User;
use App\Models\Vessel;
use App\Models\Watchlist;
use App\Models\WatchlistItem;

class WatchlistAlertService
{
    /**
     * Creates a new monitoring watchlist
     */
    public function createWatchlist(array $data, ?User $user = null): Watchlist
    {
        $watchlist = Watchlist::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? 'custom',
            'priority' => $data['priority'] ?? 'medium',
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $user?->id,
        ]);

        AuditLog::log('create_watchlist', $watchlist, null, $data);

        return $watchlist;
    }

    /**
     * Adds an entity to a watchlist
     */
    public function addItem(Watchlist $watchlist, string $entityType, string $entityId, ?string $reason = null, ?string $evidenceId = null, ?User $user = null): WatchlistItem
    {
        $item = WatchlistItem::firstOrCreate(
            [
                'watchlist_id' => $watchlist->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ],
            [
                'reason' => $reason,
                'evidence_id' => $evidenceId,
                'added_by' => $user?->id,
                'is_active' => true,
            ]
        );

        AuditLog::log('add_watchlist_item', $item, null, [
            'watchlist_name' => $watchlist->name,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);

        return $item;
    }

    /**
     * Generates an automated analyst alert
     */
    public function triggerAlert(
        string $entityType,
        string $entityId,
        string $alertType,
        string $title,
        string $details,
        string $severity = 'medium',
        ?string $watchlistId = null,
        ?string $watchlistItemId = null,
        array $contextData = []
    ): AnalystAlert {
        $year = date('Y');
        $count = AnalystAlert::whereYear('created_at', $year)->count() + 1;
        $ref = sprintf('ALT-%s-%04d', $year, $count);

        $alert = AnalystAlert::create([
            'alert_reference' => $ref,
            'watchlist_id' => $watchlistId,
            'watchlist_item_id' => $watchlistItemId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'alert_type' => $alertType,
            'severity' => $severity,
            'title' => $title,
            'details' => $details,
            'context_data' => $contextData,
            'status' => AlertStatus::ACTIVE,
        ]);

        AuditLog::log('trigger_analyst_alert', $alert, null, [
            'alert_reference' => $ref,
            'alert_type' => $alertType,
            'severity' => $severity,
        ]);

        return $alert;
    }

    /**
     * Checks if an entity is on any active watchlist and triggers an automated alert if matching
     */
    public function evaluateEntityWatchlists(string $entityType, string $entityId, string $eventTrigger, string $summary, array $context = []): ?AnalystAlert
    {
        $activeItem = WatchlistItem::with('watchlist')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->whereHas('watchlist', function ($q) {
                $q->where('is_active', true);
            })
            ->first();

        if ($activeItem) {
            $watchlist = $activeItem->watchlist;
            $severity = match ($watchlist->priority?->value ?? $watchlist->priority) {
                'critical' => 'critical',
                'high' => 'high',
                default => 'medium',
            };

            return $this->triggerAlert(
                entityType: $entityType,
                entityId: $entityId,
                alertType: $eventTrigger,
                title: "Watchlist Triggered: [{$watchlist->name}] - {$summary}",
                details: "Entity matched active watchlist '{$watchlist->name}' under category '{$watchlist->category?->value}'. Reason for monitoring: {$activeItem->reason}",
                severity: $severity,
                watchlistId: $watchlist->id,
                watchlistItemId: $activeItem->id,
                contextData: $context
            );
        }

        return null;
    }

    /**
     * Acknowledges an active alert
     */
    public function acknowledgeAlert(AnalystAlert $alert, ?User $user = null): AnalystAlert
    {
        $alert->update([
            'status' => AlertStatus::ACKNOWLEDGED,
            'acknowledged_by' => $user?->id,
            'acknowledged_at' => now(),
        ]);

        AuditLog::log('acknowledge_alert', $alert, null, ['user' => $user?->name]);

        return $alert;
    }

    /**
     * Resolves an alert with written analyst conclusion
     */
    public function resolveAlert(AnalystAlert $alert, string $notes, ?User $user = null): AnalystAlert
    {
        $alert->update([
            'status' => AlertStatus::RESOLVED,
            'resolved_by' => $user?->id,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);

        AuditLog::log('resolve_alert', $alert, null, ['resolution_notes' => $notes]);

        return $alert;
    }
}
