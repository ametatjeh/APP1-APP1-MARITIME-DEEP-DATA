<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiMessage extends Model
{
    use HasUuids;

    protected $fillable = [
        'conversation_id',
        'role',
        'agent_name',
        'content',
        'citations',
        'reasoning_trace',
        'tokens_used',
    ];

    protected function casts(): array
    {
        return [
            'citations' => 'array',
            'reasoning_trace' => 'array',
            'tokens_used' => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(AiInvestigationFinding::class, 'message_id');
    }
}
