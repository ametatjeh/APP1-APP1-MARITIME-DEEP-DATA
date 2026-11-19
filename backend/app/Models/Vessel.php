<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vessel extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'current_name',
        'current_flag_country_id',
        'imo_number',
        'current_mmsi',
        'current_callsign',
        'national_registration_number',
        'vessel_type',
        'fishing_gear_type',
        'gross_tonnage',
        'length_overall',
        'breadth',
        'draught',
        'engine_power_kw',
        'built_year',
        'shipyard',
        'port_of_registry',
        'verification_status',
        'structural_specs',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'gross_tonnage' => 'decimal:2',
            'length_overall' => 'decimal:2',
            'breadth' => 'decimal:2',
            'draught' => 'decimal:2',
            'engine_power_kw' => 'decimal:2',
            'built_year' => 'integer',
            'verification_status' => VerificationStatus::class,
            'structural_specs' => 'array',
        ];
    }

    public function currentFlagCountry()
    {
        return $this->belongsTo(Country::class, 'current_flag_country_id');
    }

    public function identifiers()
    {
        return $this->hasMany(VesselIdentifier::class);
    }

    public function names()
    {
        return $this->hasMany(VesselName::class);
    }

    public function flags()
    {
        return $this->hasMany(VesselFlag::class);
    }

    public function registrations()
    {
        return $this->hasMany(VesselRegistration::class);
    }

    public function licenses()
    {
        return $this->hasMany(VesselLicense::class);
    }

    public function photos()
    {
        return $this->hasMany(VesselPhoto::class);
    }

    public function aliases()
    {
        return $this->hasMany(VesselAlias::class);
    }

    public function conflictsAsA()
    {
        return $this->hasMany(VesselIdentityConflict::class, 'vessel_a_id');
    }

    public function conflictsAsB()
    {
        return $this->hasMany(VesselIdentityConflict::class, 'vessel_b_id');
    }

    public function companyAssignments()
    {
        return $this->hasMany(VesselCompanyAssignment::class);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'vessel_company_assignments')
                    ->withPivot(['id', 'assignment_type', 'valid_from', 'valid_to', 'is_current', 'verification_status'])
                    ->withTimestamps();
    }

    /**
     * Scope for fuzzy searching vessel names using pg_trgm similarity.
     */
    public function scopeSearchFuzzy(Builder $query, string $term, float $similarity = 0.3): Builder
    {
        return $query->where(function ($q) use ($term, $similarity) {
            $q->whereRaw('similarity(current_name, ?) > ?', [$term, $similarity])
              ->orWhere('imo_number', 'ILIKE', "%{$term}%")
              ->orWhere('current_mmsi', 'ILIKE', "%{$term}%")
              ->orWhere('current_callsign', 'ILIKE', "%{$term}%")
              ->orWhere('national_registration_number', 'ILIKE', "%{$term}%");
        });
    }
}
