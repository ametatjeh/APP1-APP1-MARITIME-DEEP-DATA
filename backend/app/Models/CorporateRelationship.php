<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CorporateRelationship extends Model
{
    use HasUuids;

    protected $fillable = [
        'parent_company_id',
        'child_company_id',
        'relationship_type',
        'ownership_percentage',
        'valid_from',
        'valid_to',
        'is_current',
        'source_record_id',
        'verification_status',
    ];

    protected function casts(): array
    {
        return [
            'ownership_percentage' => 'decimal:2',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_current' => 'boolean',
            'verification_status' => VerificationStatus::class,
        ];
    }

    public function parentCompany()
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    public function childCompany()
    {
        return $this->belongsTo(Company::class, 'child_company_id');
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }
}
