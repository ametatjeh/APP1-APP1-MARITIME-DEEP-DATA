<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VesselIdentifier extends Model
{
    use HasUuids;

    protected $fillable = [
        'vessel_id',
        'identifier_type',
        'identifier_value',
        'valid_from',
        'valid_to',
        'is_current',
        'source_record_id',
        'verification_status',
        'notes',
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

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }
}
