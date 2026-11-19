<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OverlappingClaim extends Model
{
    use HasUuids;

    protected $fillable = [
        'jurisdiction_a_id',
        'jurisdiction_b_id',
        'claim_type',
        'resolution_status',
        'analyst_notes',
    ];

    public function jurisdictionA()
    {
        return $this->belongsTo(MaritimeJurisdiction::class, 'jurisdiction_a_id');
    }

    public function jurisdictionB()
    {
        return $this->belongsTo(MaritimeJurisdiction::class, 'jurisdiction_b_id');
    }

    public function scopeWithGeoJson(Builder $query): Builder
    {
        return $query->select('*')->selectRaw('ST_AsGeoJSON(overlap_geom) as geojson');
    }

    public function setGeometryFromGeoJson(string $geoJsonString): void
    {
        DB::statement(
            'UPDATE overlapping_claims SET overlap_geom = ST_Multi(ST_GeomFromGeoJSON(?)) WHERE id = ?',
            [$geoJsonString, $this->id]
        );
    }
}
