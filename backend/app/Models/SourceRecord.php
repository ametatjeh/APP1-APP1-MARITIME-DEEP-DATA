<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SourceRecord extends Model
{
    use HasUuids;

    protected $fillable = [
        'data_source_id',
        'record_identifier',
        'record_date',
        'retrieval_date',
        'verbatim_payload',
        'sha256_hash',
        'verification_status',
        'analyst_notes',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'retrieval_date' => 'datetime',
            'verbatim_payload' => 'array',
            'verification_status' => VerificationStatus::class,
        ];
    }

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class);
    }
}
