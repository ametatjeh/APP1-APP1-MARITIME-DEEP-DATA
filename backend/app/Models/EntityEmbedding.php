<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EntityEmbedding extends Model
{
    use HasUuids;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'chunk_title',
        'content_chunk',
        'embedding',
        'dimension',
        'model_name',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'metadata' => 'array',
            'dimension' => 'integer',
        ];
    }
}
