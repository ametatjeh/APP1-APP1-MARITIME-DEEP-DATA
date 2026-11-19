<?php

namespace App\Models;

use App\Enums\QuarantineReason;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImportQuarantinedRecord extends Model
{
    use HasUuids;

    protected $fillable = [
        'data_import_id',
        'row_index',
        'raw_payload',
        'quarantine_reason',
        'error_details',
        'resolution_status',
        'reviewed_by',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'row_index' => 'integer',
            'raw_payload' => 'array',
            'quarantine_reason' => QuarantineReason::class,
        ];
    }

    public function dataImport()
    {
        return $this->belongsTo(DataImport::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
