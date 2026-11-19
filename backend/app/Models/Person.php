<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'persons';

    protected $fillable = [
        'full_name',
        'primary_role',
        'date_of_birth',
        'place_of_birth',
        'nationality_country_id',
        'gender',
        'encrypted_national_id',
        'encrypted_passport_number',
        'encrypted_seaman_book_number',
        'encrypted_tax_id',
        'masked_identifier',
        'is_pep',
        'is_sanctioned',
        'sanction_details',
        'verification_status',
        'is_redacted',
        'source_record_id',
        'data_source_id',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'encrypted_national_id' => 'encrypted',
            'encrypted_passport_number' => 'encrypted',
            'encrypted_seaman_book_number' => 'encrypted',
            'encrypted_tax_id' => 'encrypted',
            'is_pep' => 'boolean',
            'is_sanctioned' => 'boolean',
            'sanction_details' => 'array',
            'is_redacted' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function nationalityCountry()
    {
        return $this->belongsTo(Country::class, 'nationality_country_id');
    }

    public function aliases()
    {
        return $this->hasMany(PersonAlias::class);
    }

    public function identifiers()
    {
        return $this->hasMany(PersonIdentifier::class);
    }

    public function corporateTies()
    {
        return $this->hasMany(PersonCorporateTie::class);
    }

    public function vesselTies()
    {
        return $this->hasMany(PersonVesselTie::class);
    }

    public function crewEmployments()
    {
        return $this->hasMany(CrewEmployment::class);
    }

    public function labourReports()
    {
        return $this->hasMany(LabourViolationReport::class);
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class);
    }
}
