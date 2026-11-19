<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PersonIdentifier extends Model
{
    use HasUuids;

    protected $fillable = [
        'person_id',
        'identifier_type',
        'encrypted_value',
        'masked_value',
        'issuing_country_id',
        'issued_date',
        'expiry_date',
        'is_current',
        'source_record_id',
    ];

    protected function casts(): array
    {
        return [
            'encrypted_value' => 'encrypted',
            'issued_date' => 'date',
            'expiry_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
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
