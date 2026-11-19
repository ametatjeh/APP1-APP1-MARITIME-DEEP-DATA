<?php

namespace App\Models;

use App\Enums\SourceCategory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DataSource extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'code',
        'category',
        'organization_type',
        'publisher',
        'publication_date',
        'reliability_score',
        'source_url',
        'description',
        'country_id',
        'is_active',
        'verification_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'category' => SourceCategory::class,
            'reliability_score' => 'decimal:2',
            'publication_date' => 'date',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function sourceRecords()
    {
        return $this->hasMany(SourceRecord::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function evidences()
    {
        return $this->hasMany(Evidence::class, 'data_source_id');
    }
}
