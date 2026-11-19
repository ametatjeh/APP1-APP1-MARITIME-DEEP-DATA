<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EvidenceRelationship extends Model
{
    use HasUuids;

    protected $fillable = [
        'evidence_id',
        'target_type',
        'target_id',
        'relationship_type',
        'notes',
    ];

    public function evidence()
    {
        return $this->belongsTo(Evidence::class, 'evidence_id');
    }
}
