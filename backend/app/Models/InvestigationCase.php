<?php

namespace App\Models;

use App\Enums\CaseCategory;
use App\Enums\CaseConfidentiality;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestigationCase extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'case_number',
        'title',
        'summary',
        'category',
        'priority',
        'status',
        'confidentiality_level',
        'lead_investigator_id',
        'incident_date_from',
        'incident_date_to',
        'findings_summary',
        'tags',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'category' => CaseCategory::class,
            'priority' => CasePriority::class,
            'status' => CaseStatus::class,
            'confidentiality_level' => CaseConfidentiality::class,
            'incident_date_from' => 'date',
            'incident_date_to' => 'date',
            'tags' => 'array',
            'metadata' => 'array',
        ];
    }

    public function leadInvestigator()
    {
        return $this->belongsTo(User::class, 'lead_investigator_id');
    }

    public function assignments()
    {
        return $this->hasMany(CaseAssignment::class, 'case_id');
    }

    public function activeAssignments()
    {
        return $this->hasMany(CaseAssignment::class, 'case_id')->where('is_active', true);
    }

    public function investigators()
    {
        return $this->belongsToMany(User::class, 'case_assignments', 'case_id', 'user_id')
                    ->withPivot(['id', 'role_in_case', 'assigned_at', 'unassigned_at', 'is_active'])
                    ->withTimestamps();
    }

    public function notes()
    {
        return $this->hasMany(CaseNote::class, 'case_id')->orderBy('created_at', 'desc');
    }

    public function timelines()
    {
        return $this->hasMany(CaseTimeline::class, 'case_id')->orderBy('event_date', 'asc');
    }

    public function subjects()
    {
        return $this->hasMany(CaseSubject::class, 'case_id');
    }

    public function evidences()
    {
        return $this->hasMany(Evidence::class, 'case_id');
    }

    public function isUserAssigned(string $userId): bool
    {
        if ($this->lead_investigator_id === $userId) {
            return true;
        }

        return $this->activeAssignments()->where('user_id', $userId)->exists();
    }

    public static function generateCaseNumber(): string
    {
        $year = date('Y');
        $prefix = "MDD-INV-{$year}-";
        $count = self::whereYear('created_at', $year)->count() + 1;
        return $prefix . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('case_number', 'ILIKE', "%{$term}%")
              ->orWhere('title', 'ILIKE', "%{$term}%")
              ->orWhere('summary', 'ILIKE', "%{$term}%");
        });
    }
}
