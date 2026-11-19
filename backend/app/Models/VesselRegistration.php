<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VesselRegistration extends Model
{
    use HasUuids;

    protected $fillable = [
        'vessel_id',
        'country_id',
        'registration_number',
        'port_of_registry',
        'issue_date',
        'expiry_date',
        'status',
        'source_record_id',
        'verification_status',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'verification_status' => VerificationStatus::class,
        ];
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }
}
