<?php

namespace App\Models;

use App\Enums\ConflictNature;
use App\Enums\ResolutionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VesselIdentityConflict extends Model
{
    use HasUuids;

    protected $fillable = [
        'vessel_a_id',
        'vessel_b_id',
        'conflicting_identifier_type',
        'conflicting_identifier_value',
        'conflict_nature',
        'conflict_score',
        'resolution_status',
        'reviewed_by_user_id',
        'analyst_conclusion',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'conflict_nature' => ConflictNature::class,
            'resolution_status' => ResolutionStatus::class,
            'conflict_score' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function vesselA()
    {
        return $this->belongsTo(Vessel::class, 'vessel_a_id');
    }

    public function vesselB()
    {
        return $this->belongsTo(Vessel::class, 'vessel_b_id');
    }

    public function reviewedByUser()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
