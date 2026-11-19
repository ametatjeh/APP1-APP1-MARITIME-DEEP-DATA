<?php

namespace App\Models;

use App\Enums\JurisdictionType;
use App\Enums\LegalStatus;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MaritimeJurisdiction extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'jurisdiction_type',
        'area_name',
        'legal_basis',
        'legal_status',
        'ratification_status',
        'effective_date',
        'source_record_id',
        'verification_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'jurisdiction_type' => JurisdictionType::class,
            'legal_status' => LegalStatus::class,
            'verification_status' => VerificationStatus::class,
            'effective_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }

    public function overlappingClaimsAsA()
    {
        return $this->hasMany(OverlappingClaim::class, 'jurisdiction_a_id');
    }

    public function overlappingClaimsAsB()
    {
        return $this->hasMany(OverlappingClaim::class, 'jurisdiction_b_id');
    }

    /**
     * Scope to select PostGIS geometry as GeoJSON string.
     */
    public function scopeWithGeoJson(Builder $query): Builder
    {
        return $query->select('*')->selectRaw('ST_AsGeoJSON(geom) as geojson');
    }

    /**
     * Scope to find jurisdictions containing a given latitude/longitude point.
     */
    public function scopeContainingPoint(Builder $query, float $longitude, float $latitude): Builder
    {
        return $query->whereRaw(
            'ST_Contains(geom, ST_SetSRID(ST_Point(?, ?), 4326))',
            [$longitude, $latitude]
        );
    }

    /**
     * Scope to find jurisdictions intersecting a given bounding box.
     */
    public function scopeIntersectingBBox(Builder $query, float $minLon, float $minLat, float $maxLon, float $maxLat): Builder
    {
        return $query->whereRaw(
            'ST_Intersects(geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))',
            [$minLon, $minLat, $maxLon, $maxLat]
        );
    }

    /**
     * Set geometry from GeoJSON string or polygon array.
     */
    public function setGeometryFromGeoJson(string $geoJsonString): void
    {
        DB::statement(
            'UPDATE maritime_jurisdictions SET geom = ST_Multi(ST_GeomFromGeoJSON(?)) WHERE id = ?',
            [$geoJsonString, $this->id]
        );
    }
}
