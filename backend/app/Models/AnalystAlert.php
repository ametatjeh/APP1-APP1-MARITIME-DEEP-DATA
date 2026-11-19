<?php

namespace App\Models;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AnalystAlert extends Model
{
    use HasUuids;

    protected $fillable = [
        'alert_reference',
        'watchlist_id',
        'watchlist_item_id',
        'entity_type',
        'entity_id',
        'alert_type',
        'severity',
        'title',
        'details',
        'context_data',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'severity' => AlertSeverity::class,
            'status' => AlertStatus::class,
            'context_data' => 'array',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function watchlist()
    {
        return $this->belongsTo(Watchlist::class);
    }

    public function watchlistItem()
    {
        return $this->belongsTo(WatchlistItem::class);
    }

    public function acknowledgedByUser()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedByUser()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getEntityAttribute()
    {
        return match ($this->entity_type) {
            'vessel' => Vessel::find($this->entity_id),
            'company' => Company::find($this->entity_id),
            'person' => Person::find($this->entity_id),
            default => null,
        };
    }
}
