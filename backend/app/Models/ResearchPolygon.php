<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ResearchPolygon extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'category',
        'description',
        'created_by',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeWithGeoJson($query)
    {
        return $query->addSelect([
            'id',
            'name',
            'category',
            'description',
            'created_by',
            'is_active',
            'metadata',
            'created_at',
            'updated_at',
            DB::raw('ST_AsGeoJSON(geom) as geojson'),
        ]);
    }
}
