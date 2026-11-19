<?php

namespace App\Models;

use App\Enums\SubjectRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CaseSubject extends Model
{
    use HasUuids;

    protected $fillable = [
        'case_id',
        'subject_type',
        'subject_id',
        'subject_name',
        'subject_role',
        'involvement_summary',
        'date_identified',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'subject_role' => SubjectRole::class,
            'date_identified' => 'date',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function case()
    {
        return $this->belongsTo(InvestigationCase::class, 'case_id');
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class, 'subject_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'subject_id');
    }

    /**
     * Resolve the underlying entity if available.
     */
    public function getResolvedEntityAttribute()
    {
        if ($this->subject_type === 'vessel' && $this->subject_id) {
            return Vessel::with('currentFlagCountry')->find($this->subject_id);
        }

        if ($this->subject_type === 'company' && $this->subject_id) {
            return Company::with('countryOfIncorporation')->find($this->subject_id);
        }

        return null;
    }
}
