<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ReportCitation extends Model
{
    use HasUuids;

    protected $fillable = [
        'research_report_id',
        'evidence_id',
        'source_record_id',
        'citation_key',
        'statement_supported',
    ];

    public function researchReport()
    {
        return $this->belongsTo(ResearchReport::class);
    }

    public function evidence()
    {
        return $this->belongsTo(Evidence::class);
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }
}
