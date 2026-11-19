<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VesselLicense extends Model
{
    use HasUuids;

    protected $fillable = [
        'vessel_id',
        'issuing_country_id',
        'license_number',
        'license_type',
        'authorized_gear',
        'target_species',
        'authorized_zones',
        'valid_from',
        'valid_to',
        'status',
        'source_record_id',
        'verification_status',
    ];

    protected function casts(): array
    {
        return [
            'authorized_zones' => 'array',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'verification_status' => VerificationStatus::class,
        ];
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function issuingCountry()
    {
        return $this->belongsTo(Country::class, 'issuing_country_id');
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }
}
