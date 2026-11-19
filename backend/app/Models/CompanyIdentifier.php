<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CompanyIdentifier extends Model
{
    use HasUuids;

    protected $fillable = [
        'company_id',
        'identifier_type',
        'identifier_value',
        'issuing_country_id',
        'is_current',
        'source_record_id',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function issuingCountry()
    {
        return $this->belongsTo(Country::class, 'issuing_country_id');
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }
}
