<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VesselPhoto extends Model
{
    use HasUuids;

    protected $fillable = [
        'vessel_id',
        'file_path',
        'thumbnail_path',
        'sha256_hash',
        'file_size_bytes',
        'mime_type',
        'caption',
        'photo_date',
        'taken_at_location',
        'is_primary',
        'source_record_id',
    ];

    protected function casts(): array
    {
        return [
            'photo_date' => 'date',
            'is_primary' => 'boolean',
            'file_size_bytes' => 'integer',
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
