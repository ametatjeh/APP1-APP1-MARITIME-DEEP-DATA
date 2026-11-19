<?php

namespace App\Models;

use App\Enums\WatchlistCategory;
use App\Enums\WatchlistPriority;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Watchlist extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'category',
        'priority',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => WatchlistCategory::class,
            'priority' => WatchlistPriority::class,
            'is_active' => 'boolean',
        ];
    }

    public function items()
    {
        return $this->hasMany(WatchlistItem::class);
    }

    public function alerts()
    {
        return $this->hasMany(AnalystAlert::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
