<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CompanyOfficer extends Model
{
    use HasUuids;

    protected $fillable = [
        'company_id',
        'officer_name',
        'position',
        'appointment_date',
        'resignation_date',
        'is_current',
        'is_redacted',
        'source_record_id',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'resignation_date' => 'date',
            'is_current' => 'boolean',
            'is_redacted' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }

    /**
     * Return redacted name for unauthorized users if marked as redacted.
     */
    public function getSafeOfficerName(): string
    {
        if ($this->is_redacted) {
            $user = auth()->user();
            if (!$user || !$user->role instanceof UserRole || !$user->role->canViewRedactedPII()) {
                return '[REDACTED OFFICER - RESTRICTED ACCESS]';
            }
        }

        return $this->officer_name;
    }
}
