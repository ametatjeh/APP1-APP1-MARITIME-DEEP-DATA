<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ManningAgent extends Model
{
    use HasUuids;

    protected $fillable = [
        'agent_name',
        'license_number',
        'country_id',
        'registered_address',
        'compliance_rating',
        'past_violations_summary',
        'verification_status',
        'source_record_id',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function employments()
    {
        return $this->hasMany(CrewEmployment::class);
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }
}
