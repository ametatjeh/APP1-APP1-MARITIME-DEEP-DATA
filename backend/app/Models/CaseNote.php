<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CaseNote extends Model
{
    use HasUuids;

    protected $fillable = [
        'case_id',
        'user_id',
        'title',
        'note_text',
        'note_type',
        'is_confidential',
    ];

    protected function casts(): array
    {
        return [
            'is_confidential' => 'boolean',
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
}
