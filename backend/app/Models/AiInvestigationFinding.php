<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiInvestigationFinding extends Model
{
    use HasUuids;

    protected $fillable = [
        'conversation_id',
        'message_id',
        'finding_type',
        'title',
        'summary',
        'evidence_citations',
        'confidence_level',
        'is_verified_by_analyst',
        'analyst_review_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence_citations' => 'array',
            'is_verified_by_analyst' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(AiMessage::class, 'message_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
