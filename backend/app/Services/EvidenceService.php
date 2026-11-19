<?php

namespace App\Services;

use App\Enums\CustodyAction;
use App\Enums\EvidenceClassification;
use App\Enums\EvidenceType;
use App\Enums\EvidenceVerificationStatus;
use App\Models\AuditLog;
use App\Models\ChainOfCustody;
use App\Models\Evidence;
use App\Models\EvidenceRelationship;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceService
{
    /**
     * Store uploaded evidence in private storage, compute SHA-256 checksum, and initialize Chain of Custody.
     */
    public function storeEvidence(UploadedFile $file, array $attributes, User $uploader): Evidence
    {
        return DB::transaction(function () use ($file, $attributes, $uploader) {
            $originalFileName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType() ?? 'application/octet-stream';
            $fileSizeBytes = $file->getSize();

            // Calculate SHA-256 hash from temporary upload stream
            $sha256Hash = hash_file('sha256', $file->getRealPath());

            // Generate UUID-based private storage filename
            $storageDir = 'evidence/' . date('Y/m');
            $uniqueFileName = (string) Str::uuid() . '.' . $extension;
            $storedPath = $file->storeAs($storageDir, $uniqueFileName, 'private');

            $evidence = Evidence::create([
                'case_id' => $attributes['case_id'] ?? null,
                'title' => $attributes['title'] ?? pathinfo($originalFileName, PATHINFO_FILENAME),
                'evidence_type' => $attributes['evidence_type'] ?? EvidenceType::DOCUMENT,
                'classification' => $attributes['classification'] ?? EvidenceClassification::CONFIDENTIAL,
                'file_path' => $storedPath,
                'file_name' => $originalFileName,
                'file_extension' => $extension,
                'mime_type' => $mimeType,
                'file_size_bytes' => $fileSizeBytes,
                'sha256_hash' => $sha256Hash,
                'data_source_id' => $attributes['data_source_id'] ?? null,
                'source_record_id' => $attributes['source_record_id'] ?? null,
                'source_url' => $attributes['source_url'] ?? null,
                'collection_date' => $attributes['collection_date'] ?? now()->toDateString(),
                'publication_date' => $attributes['publication_date'] ?? null,
                'verification_status' => EvidenceVerificationStatus::PENDING_VERIFICATION,
                'collected_by_user_id' => $uploader->id,
                'metadata' => $attributes['metadata'] ?? null,
            ]);

            // Append initial immutable events to Chain of Custody
            ChainOfCustody::recordEvent(
                $evidence,
                CustodyAction::UPLOADED,
                $uploader,
                "Uploaded file '{$originalFileName}' ({$fileSizeBytes} bytes) to private storage vault.",
                ['storage_path' => $storedPath]
            );

            ChainOfCustody::recordEvent(
                $evidence,
                CustodyAction::HASH_GENERATED,
                $uploader,
                "Generated cryptographic SHA-256 checksum: {$sha256Hash}",
                ['hash' => $sha256Hash]
            );

            AuditLog::log('evidence_uploaded', $evidence, null, [
                'sha256' => $sha256Hash,
                'size' => $fileSizeBytes,
                'uploader_id' => $uploader->id,
            ]);

            return $evidence->load(['case', 'dataSource', 'collectedByUser']);
        });
    }

    /**
     * Download or stream evidence file verifying SHA-256 integrity and appending to Chain of Custody.
     */
    public function downloadEvidence(Evidence $evidence, User $accessor): StreamedResponse
    {
        // Verify SHA-256 cryptographic integrity before delivering
        if (!$evidence->verifyFileIntegrity()) {
            abort(500, 'CRITICAL SECURITY ALERT: Evidence file failed SHA-256 cryptographic integrity verification. File may be corrupted or tampered with.');
        }

        // Record in Chain of Custody
        ChainOfCustody::recordEvent(
            $evidence,
            CustodyAction::DOWNLOADED,
            $accessor,
            "Evidence binary downloaded by analyst {$accessor->name} ({$accessor->role?->value})."
        );

        AuditLog::log('evidence_downloaded', $evidence, null, [
            'accessor_id' => $accessor->id,
            'sha256' => $evidence->sha256_hash,
        ]);

        return Storage::disk('private')->download(
            $evidence->file_path,
            $evidence->file_name,
            [
                'Content-Type' => $evidence->mime_type,
                'X-Evidence-SHA256' => $evidence->sha256_hash,
            ]
        );
    }

    /**
     * Map evidence relationship to target entity (vessel, company, case subject).
     */
    public function linkRelationship(
        Evidence $evidence,
        string $targetType,
        string $targetId,
        string $relationshipType,
        ?string $notes = null
    ): EvidenceRelationship {
        $rel = EvidenceRelationship::create([
            'evidence_id' => $evidence->id,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'relationship_type' => $relationshipType,
            'notes' => $notes,
        ]);

        AuditLog::log('evidence_linked', $evidence, null, [
            'target_type' => $targetType,
            'target_id' => $targetId,
            'relationship' => $relationshipType,
        ]);

        return $rel;
    }
}
