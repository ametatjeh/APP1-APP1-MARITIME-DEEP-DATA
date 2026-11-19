<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CountryDataset extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'dataset_name',
        'dataset_type',
        'data_source_id',
        'version',
        'last_updated_at',
        'records_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'last_updated_at' => 'datetime',
            'records_count' => 'integer',
        ];
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class);
    }
}
