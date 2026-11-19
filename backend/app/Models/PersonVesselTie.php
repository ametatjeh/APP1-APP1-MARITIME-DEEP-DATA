<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PersonVesselTie extends Model
{
    use HasUuids;

    protected $fillable = [
        'person_id',
        'vessel_id',
        'role',
        'effective_from',
        'effective_to',
        'license_number',
        'is_current',
        'verification_status',
        'source_record_id',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
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
