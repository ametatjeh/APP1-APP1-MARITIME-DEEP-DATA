<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'company_name',
        'registration_number',
        'country_of_incorporation_id',
        'incorporation_date',
        'dissolution_date',
        'legal_form',
        'corporate_status',
        'registered_address',
        'is_sanctioned',
        'sanction_details',
        'verification_status',
        'source_record_id',
    ];

    protected function casts(): array
    {
        return [
            'incorporation_date' => 'date',
            'dissolution_date' => 'date',
            'is_sanctioned' => 'boolean',
            'sanction_details' => 'array',
            'verification_status' => VerificationStatus::class,
        ];
    }

    public function countryOfIncorporation()
    {
        return $this->belongsTo(Country::class, 'country_of_incorporation_id');
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }

    public function identifiers()
    {
        return $this->hasMany(CompanyIdentifier::class);
    }

    public function officers()
    {
        return $this->hasMany(CompanyOfficer::class);
    }

    public function shareholders()
    {
        return $this->hasMany(CompanyShareholder::class);
    }

    public function subsidiaryRelationships()
    {
        return $this->hasMany(CorporateRelationship::class, 'parent_company_id');
    }

    public function parentRelationships()
    {
        return $this->hasMany(CorporateRelationship::class, 'child_company_id');
    }

    public function personTies()
    {
        return $this->hasMany(PersonCorporateTie::class);
    }

    public function vesselAssignments()
    {
        return $this->hasMany(VesselCompanyAssignment::class);
    }

    public function vessels()
    {
        return $this->belongsToMany(Vessel::class, 'vessel_company_assignments')
                    ->withPivot(['id', 'assignment_type', 'valid_from', 'valid_to', 'is_current', 'verification_status'])
                    ->withTimestamps();
    }

    public function scopeSearchFuzzy(Builder $query, string $term, float $similarity = 0.3): Builder
    {
        return $query->where(function ($q) use ($term, $similarity) {
            $q->whereRaw('similarity(company_name, ?) > ?', [$term, $similarity])
              ->orWhere('registration_number', 'ILIKE', "%{$term}%");
        });
    }
}
