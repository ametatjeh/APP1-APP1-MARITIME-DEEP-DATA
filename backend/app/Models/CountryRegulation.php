<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CountryRegulation extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'title',
        'regulation_number',
        'issuing_authority',
        'effective_date',
        'repeal_date',
        'status',
        'summary',
        'source_record_id',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'repeal_date' => 'date',
        ];
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }
}
