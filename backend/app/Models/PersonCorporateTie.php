<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PersonCorporateTie extends Model
{
    use HasUuids;

    protected $fillable = [
        'person_id',
        'company_id',
        'role',
        'shares_percentage',
        'effective_from',
        'effective_to',
        'is_current',
        'verification_status',
        'source_record_id',
    ];

    protected function casts(): array
    {
        return [
            'shares_percentage' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }
}
