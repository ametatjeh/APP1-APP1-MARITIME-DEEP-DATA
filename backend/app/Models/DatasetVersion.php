<?php

namespace App\Models;

use App\Enums\DatasetStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DatasetVersion extends Model
{
    use HasUuids;

    protected $fillable = [
        'data_source_id',
        'country_id',
        'dataset_name',
        'version_tag',
        'publication_date',
        'effective_date',
        'deprecation_date',
        'is_current',
        'snapshot_sha256',
        'records_count',
        'schema_definition',
        'changelog',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'publication_date' => 'date',
            'effective_date' => 'date',
            'deprecation_date' => 'date',
            'is_current' => 'boolean',
            'records_count' => 'integer',
            'schema_definition' => 'array',
            'status' => DatasetStatus::class,
        ];
    }

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function imports()
    {
        return $this->hasMany(DataImport::class);
    }
}
