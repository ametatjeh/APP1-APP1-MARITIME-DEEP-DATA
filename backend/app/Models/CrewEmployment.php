<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CrewEmployment extends Model
{
    use HasUuids;

    protected $fillable = [
        'person_id',
        'vessel_id',
        'manning_agent_id',
        'role_on_board',
        'embarkation_date',
        'embarkation_port',
        'disembarkation_date',
        'disembarkation_port',
        'contract_status',
        'salary_currency',
        'reported_monthly_wage',
        'is_forced_labour_suspected',
        'verification_status',
        'source_record_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'embarkation_date' => 'date',
            'disembarkation_date' => 'date',
            'reported_monthly_wage' => 'decimal:2',
            'is_forced_labour_suspected' => 'boolean',
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

    public function manningAgent()
    {
        return $this->belongsTo(ManningAgent::class);
    }

    public function labourReports()
    {
        return $this->hasMany(LabourViolationReport::class);
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }
}
