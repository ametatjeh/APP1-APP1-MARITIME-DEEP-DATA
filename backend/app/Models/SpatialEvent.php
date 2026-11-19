<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SpatialEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'event_type',
        'event_date',
        'vessel_id',
        'investigation_case_id',
        'maritime_jurisdiction_id',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function investigationCase()
    {
        return $this->belongsTo(InvestigationCase::class);
    }

    public function maritimeJurisdiction()
    {
        return $this->belongsTo(MaritimeJurisdiction::class);
    }

    public function scopeWithCoordinates($query)
    {
        return $query->addSelect([
            '*',
            DB::raw('ST_X(geom) as longitude'),
            DB::raw('ST_Y(geom) as latitude'),
            DB::raw('ST_AsGeoJSON(geom) as geojson'),
        ]);
    }
}
