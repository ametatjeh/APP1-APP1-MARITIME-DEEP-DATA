<?php

namespace App\Models;

use App\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DataImport extends Model
{
    use HasUuids;

    protected $fillable = [
        'batch_reference',
        'data_source_id',
        'dataset_version_id',
        'country_id',
        'source_filename',
        'file_format',
        'file_size_bytes',
        'file_sha256',
        'target_entity_type',
        'status',
        'total_rows',
        'processed_rows',
        'valid_rows_count',
        'quarantined_rows_count',
        'duplicate_rows_count',
        'validation_rules_applied',
        'summary_report',
        'created_by',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ImportStatus::class,
            'file_size_bytes' => 'integer',
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'valid_rows_count' => 'integer',
            'quarantined_rows_count' => 'integer',
            'duplicate_rows_count' => 'integer',
            'validation_rules_applied' => 'array',
            'summary_report' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class);
    }

    public function datasetVersion()
    {
        return $this->belongsTo(DatasetVersion::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quarantinedRecords()
    {
        return $this->hasMany(ImportQuarantinedRecord::class);
    }
}
