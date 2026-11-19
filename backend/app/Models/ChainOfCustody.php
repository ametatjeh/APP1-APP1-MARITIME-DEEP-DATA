<?php

namespace App\Models;

use App\Enums\CustodyAction;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ChainOfCustody extends Model
{
    use HasUuids;

    public $timestamps = false; // Immutable, only created_at is populated

    protected $fillable = [
        'evidence_id',
        'action',
        'actor_id',
        'actor_name',
        'actor_role',
        'ip_address',
        'user_agent',
        'hash_at_action',
        'notes',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => CustodyAction::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function evidence()
    {
        return $this->belongsTo(Evidence::class, 'evidence_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Factory method to append an immutable record to the chain of custody.
     */
    public static function recordEvent(
        Evidence $evidence,
        CustodyAction $action,
        ?User $actor = null,
        ?string $notes = null,
        ?array $metadata = null
    ): self {
        $actor = $actor ?? auth()->user();

        return self::create([
            'evidence_id' => $evidence->id,
            'action' => $action,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'System Automated Daemon',
            'actor_role' => $actor?->role?->value ?? 'system',
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'CLI/System',
            'hash_at_action' => $evidence->sha256_hash,
            'notes' => $notes,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
