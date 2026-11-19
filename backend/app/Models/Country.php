<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasUuids;

    protected $fillable = [
        'iso_code_2',
        'iso_code_3',
        'numeric_code',
        'name',
        'official_name',
        'flag_icon_url',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function regulations()
    {
        return $this->hasMany(CountryRegulation::class);
    }

    public function datasets()
    {
        return $this->hasMany(CountryDataset::class);
    }

    public function maritimeJurisdictions()
    {
        return $this->hasMany(MaritimeJurisdiction::class);
    }

    public function vessels()
    {
        return $this->hasMany(Vessel::class, 'current_flag_country_id');
    }

    public function companies()
    {
        return $this->hasMany(Company::class, 'country_of_incorporation_id');
    }
}
