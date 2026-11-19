<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PersonAlias extends Model
{
    use HasUuids;

    protected $fillable = [
        'person_id',
        'alias_name',
        'alias_type',
        'notes',
        'source_record_id',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class);
    }
}
