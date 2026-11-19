<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CompanyShareholder extends Model
{
    use HasUuids;

    protected $fillable = [
        'company_id',
        'shareholder_name',
        'shareholder_type',
        'share_percentage',
        'shares_count',
        'valid_from',
        'valid_to',
        'is_current',
        'source_record_id',
    ];

    protected function casts(): array
    {
        return [
            'share_percentage' => 'decimal:2',
            'shares_count' => 'integer',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_current' => 'boolean',
        ];
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
