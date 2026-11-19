<?php

namespace App\Models;

use App\Enums\ReportType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ResearchReport extends Model
{
    use HasUuids;

    protected $fillable = [
        'report_reference',
        'title',
        'report_type',
        'classification',
        'target_entity_type',
        'target_entity_id',
        'executive_summary',
        'body_markdown',
        'structured_payload',
        'sha256_checksum',
        'investigation_case_id',
        'created_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'report_type' => ReportType::class,
            'structured_payload' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function citations()
    {
        return $this->hasMany(ReportCitation::class);
    }

    public function investigationCase()
    {
        return $this->belongsTo(InvestigationCase::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTargetEntityAttribute()
    {
        return match ($this->target_entity_type) {
            'vessel' => Vessel::find($this->target_entity_id),
            'company' => Company::find($this->target_entity_id),
            'person' => Person::find($this->target_entity_id),
            'case' => InvestigationCase::find($this->target_entity_id),
            default => null,
        };
    }
}
