<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WatchlistItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'watchlist_id',
        'entity_type',
        'entity_id',
        'reason',
        'evidence_id',
        'added_by',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function watchlist()
    {
        return $this->belongsTo(Watchlist::class);
    }

    public function evidence()
    {
        return $this->belongsTo(Evidence::class);
    }

    public function addedByUser()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function alerts()
    {
        return $this->hasMany(AnalystAlert::class);
    }

    /**
     * Resolves the linked entity model dynamically
     */
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
