<?php

namespace App\Models;

use App\Enums\CaseRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CaseAssignment extends Model
{
    use HasUuids;

    protected $fillable = [
        'case_id',
        'user_id',
        'role_in_case',
        'assigned_at',
        'unassigned_at',
        'is_active',
        'assigned_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'role_in_case' => CaseRole::class,
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function case()
    {
        return $this->belongsTo(InvestigationCase::class, 'case_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
