<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VesselFlag extends Model
{
    use HasUuids;

    protected $fillable = [
        'vessel_id',
        'country_id',
        'valid_from',
        'valid_to',
        'is_current',
        'source_record_id',
        'verification_status',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_current' => 'boolean',
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
