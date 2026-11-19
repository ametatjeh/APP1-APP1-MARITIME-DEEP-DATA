<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DataSource extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'code',
        'organization_type',
        'reliability_score',
        'source_url',
        'description',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'reliability_score' => 'decimal:2',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function sourceRecords()
    {
        return $this->hasMany(SourceRecord::class);
    }
}
