<?php

namespace App\Models;

use App\Enums\EvidenceClassification;
use App\Enums\EvidenceType;
use App\Enums\EvidenceVerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Evidence extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'evidences';

    protected $fillable = [
        'case_id',
        'title',
        'evidence_type',
        'classification',
        'file_path',
        'file_name',
        'file_extension',
        'mime_type',
        'file_size_bytes',
        'sha256_hash',
        'data_source_id',
        'source_record_id',
        'source_url',
        'collection_date',
        'publication_date',
        'verification_status',
        'collected_by_user_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'evidence_type' => EvidenceType::class,
            'classification' => EvidenceClassification::class,
            'verification_status' => EvidenceVerificationStatus::class,
            'collection_date' => 'date',
            'publication_date' => 'date',
            'file_size_bytes' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function case()
    {
        return $this->belongsTo(InvestigationCase::class, 'case_id');
    }

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class, 'data_source_id');
    }

    public function sourceRecord()
    {
        return $this->belongsTo(SourceRecord::class, 'source_record_id');
    }

    public function collectedByUser()
    {
        return $this->belongsTo(User::class, 'collected_by_user_id');
    }

    public function relationships()
    {
        return $this->hasMany(EvidenceRelationship::class, 'evidence_id');
    }

    public function custodyLogs()
    {
        return $this->hasMany(ChainOfCustody::class, 'evidence_id')->orderBy('created_at', 'desc');
    }

    public function verificationReviews()
    {
        return $this->morphMany(VerificationReview::class, 'verifiable')->orderBy('reviewed_at', 'desc');
    }

    /**
     * Verify that the physical file on disk matches the stored SHA-256 checksum.
     */
    public function verifyFileIntegrity(): bool
    {
        if (!Storage::disk('private')->exists($this->file_path)) {
            return false;
        }

        $fullPath = Storage::disk('private')->path($this->file_path);
        $currentHash = hash_file('sha256', $fullPath);

        return hash_equals($this->sha256_hash, $currentHash);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'ILIKE', "%{$term}%")
              ->orWhere('file_name', 'ILIKE', "%{$term}%")
              ->orWhere('sha256_hash', 'ILIKE', "%{$term}%");
        });
    }
}
