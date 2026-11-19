<?php

namespace App\Models;

use App\Enums\EvidenceVerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VerificationReview extends Model
{
    use HasUuids;

    protected $fillable = [
        'verifiable_type',
        'verifiable_id',
        'reviewer_id',
        'previous_status',
        'new_status',
        'decision',
        'written_conclusion',
        'supporting_evidence_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'new_status' => EvidenceVerificationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function verifiable()
    {
        return $this->morphTo();
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function supportingEvidence()
    {
        return $this->belongsTo(Evidence::class, 'supporting_evidence_id');
    }
}
