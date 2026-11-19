<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VesselAlias extends Model
{
    use HasUuids;

    protected $fillable = [
        'vessel_id',
        'alias_name',
        'alias_type',
        'source_record_id',
        'confidence_score',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'decimal:2',
        ];
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }
}
