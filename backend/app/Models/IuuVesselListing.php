<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IuuVesselListing extends Model
{
    use HasUuids;

    protected $fillable = [
        'vessel_id',
        'list_name',
        'listing_organization',
        'listing_date',
        'delisting_date',
        'listing_reason',
        'listing_status',
        'reported_flag_state',
        'reported_owner',
        'reported_master',
        'official_reference_number',
        'source_url',
        'data_source_id',
        'verification_status',
    ];

    protected function casts(): array
    {
        return [
            'listing_date' => 'date',
            'delisting_date' => 'date',
        ];
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class);
    }
}
