<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LabourViolationReport extends Model
{
    use HasUuids;

    protected $fillable = [
        'crew_employment_id',
        'vessel_id',
        'person_id',
        'indicator_type',
        'severity',
        'incident_date',
        'incident_description',
        'reported_by',
        'reporting_organization',
        'is_verified',
        'verification_status',
        'source_record_id',
        'investigation_case_id',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'is_verified' => 'boolean',
        ];
    }

    public function crewEmployment()
    {
        return $this->belongsTo(CrewEmployment::class);
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function investigationCase()
    {
        return $this->belongsTo(InvestigationCase::class);
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }
}
