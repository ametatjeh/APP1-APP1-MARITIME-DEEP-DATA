<?php

namespace App\Models;

use App\Enums\CompanyAssignmentType;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VesselCompanyAssignment extends Model
{
    use HasUuids;

    protected $fillable = [
        'vessel_id',
        'company_id',
        'assignment_type',
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
            'assignment_type' => CompanyAssignmentType::class,
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

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }
}
