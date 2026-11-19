<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CaseTimeline extends Model
{
    use HasUuids;

    protected $fillable = [
        'case_id',
        'event_date',
        'event_title',
        'event_description',
        'event_type',
        'source_record_id',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
        ];
    }

    public function case()
    {
        return $this->belongsTo(InvestigationCase::class, 'case_id');
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class, 'source_record_id');
    }
}
