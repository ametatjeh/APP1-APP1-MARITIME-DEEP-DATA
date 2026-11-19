<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class IuuInfractionEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'vessel_id',
        'infraction_type',
        'event_category',
        'severity',
        'event_date',
        'location_text',
        'maritime_jurisdiction_id',
        'description',
        'reporting_agency',
        'action_taken',
        'investigation_case_id',
        'data_source_id',
        'verification_status',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function maritimeJurisdiction()
    {
        return $this->belongsTo(MaritimeJurisdiction::class);
    }

    public function investigationCase()
    {
        return $this->belongsTo(InvestigationCase::class);
    }

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class);
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
