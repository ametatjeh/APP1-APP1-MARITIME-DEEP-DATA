<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MaritimeBoundaryClaim extends Model
{
    use HasUuids;

    protected $fillable = [
        'claimant_country_id',
        'opposing_country_id',
        'dispute_name',
        'boundary_type',
        'claim_description',
        'legal_basis_claimant',
        'legal_basis_opposing',
        'status',
        'source_record_id',
    ];

    public function claimantCountry()
    {
        return $this->belongsTo(Country::class, 'claimant_country_id');
    }

    public function opposingCountry()
    {
        return $this->belongsTo(Country::class, 'opposing_country_id');
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }

    public function scopeWithGeoJson(Builder $query): Builder
    {
        return $query->select('*')->selectRaw('ST_AsGeoJSON(geom) as geojson');
    }

    public function setGeometryFromGeoJson(string $geoJsonString): void
    {
        DB::statement(
            'UPDATE maritime_boundary_claims SET geom = ST_Multi(ST_GeomFromGeoJSON(?)) WHERE id = ?',
            [$geoJsonString, $this->id]
        );
    }
}
