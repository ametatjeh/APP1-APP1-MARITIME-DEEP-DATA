<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Enums\QuarantineReason;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Country;
use App\Models\DataImport;
use App\Models\DataSource;
use App\Models\DatasetVersion;
use App\Models\ImportQuarantinedRecord;
use App\Models\Person;
use App\Models\SourceRecord;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselFlag;
use App\Models\VesselIdentifier;
use App\Models\VesselName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataImportService
{
    public function __construct(
        protected DataValidationService $validator
    ) {}

    /**
     * Executes end-to-end import pipeline with strict validation and quarantining
     */
    public function processBatch(
        DataSource $source,
        string $filename,
        string $fileFormat,
        string $targetEntityType,
        array $rows,
        ?DatasetVersion $datasetVersion = null,
        ?string $countryId = null,
        ?User $user = null,
        array $validationRules = []
    ): DataImport {
        // Generate sequential reference: IMP-YYYY-XXXX
        $year = date('Y');
        $count = DataImport::whereYear('created_at', $year)->count() + 1;
        $batchRef = sprintf('IMP-%s-%04d', $year, $count);

        $payloadJson = json_encode($rows);
        $fileSha256 = hash('sha256', $payloadJson);

        $dataImport = DataImport::create([
            'batch_reference' => $batchRef,
            'data_source_id' => $source->id,
            'dataset_version_id' => $datasetVersion?->id,
            'country_id' => $countryId,
            'source_filename' => $filename,
            'file_format' => $fileFormat,
            'file_size_bytes' => strlen($payloadJson),
            'file_sha256' => $fileSha256,
            'target_entity_type' => $targetEntityType,
            'status' => ImportStatus::IMPORTING,
            'total_rows' => count($rows),
            'processed_rows' => 0,
            'valid_rows_count' => 0,
            'quarantined_rows_count' => 0,
            'duplicate_rows_count' => 0,
            'validation_rules_applied' => $validationRules,
            'created_by' => $user?->id,
            'started_at' => now(),
        ]);

        $validCount = 0;
        $quarantineCount = 0;
        $duplicateCount = 0;
        $createdEntities = [];

        foreach ($rows as $index => $row) {
            $validation = $this->validator->validateRow($row, $targetEntityType, $validationRules);

            if (!$validation['is_valid']) {
                $quarantineReason = QuarantineReason::SCHEMA_ERROR;
                if (str_contains($validation['error_message'], 'IMO')) {
                    $quarantineReason = QuarantineReason::INVALID_IMO_CHECKSUM;
                } elseif (str_contains($validation['error_message'], 'MMSI')) {
                    $quarantineReason = QuarantineReason::INVALID_MMSI_LENGTH;
                }

                ImportQuarantinedRecord::create([
                    'data_import_id' => $dataImport->id,
                    'row_index' => $index + 1,
                    'raw_payload' => $row,
                    'quarantine_reason' => $quarantineReason,
                    'error_details' => $validation['error_message'],
                    'resolution_status' => 'pending_review',
                ]);
                $quarantineCount++;
                continue;
            }

            // Ingest valid row into master database with source record provenance
            try {
                DB::beginTransaction();

                $sourceRecord = SourceRecord::create([
                    'data_source_id' => $source->id,
                    'record_identifier' => $row['record_id'] ?? $row['id'] ?? "BATCH-{$batchRef}-ROW-{$index}",
                    'record_date' => $row['record_date'] ?? date('Y-m-d'),
                    'verbatim_payload' => $row,
                    'sha256_hash' => hash('sha256', json_encode($row)),
                    'verification_status' => 'unverified',
                    'analyst_notes' => "Imported via pipeline {$batchRef}",
                ]);

                if ($targetEntityType === 'vessel') {
                    $flagId = $this->resolveFlagCountryId($row['flag'] ?? $row['flag_state'] ?? null, $countryId);
                    $vessel = Vessel::create([
                        'current_name' => $row['name'] ?? $row['current_name'] ?? $row['vessel_name'] ?? 'UNKNOWN VESSEL',
                        'imo_number' => $row['imo'] ?? $row['imo_number'] ?? null,
                        'current_mmsi' => $row['mmsi'] ?? $row['current_mmsi'] ?? null,
                        'current_callsign' => $row['callsign'] ?? $row['current_callsign'] ?? null,
                        'current_flag_country_id' => $flagId,
                        'vessel_type' => $row['vessel_type'] ?? 'fishing',
                        'length_overall' => $row['length'] ?? $row['length_overall'] ?? null,
                        'gross_tonnage' => $row['tonnage'] ?? $row['gross_tonnage'] ?? null,
                        'verification_status' => VerificationStatus::UNVERIFIED,
                    ]);

                    VesselName::create([
                        'vessel_id' => $vessel->id,
                        'name' => $vessel->current_name,
                        'is_current' => true,
                        'valid_from' => now()->toDateString(),
                    ]);

                    if (!empty($vessel->imo_number)) {
                        VesselIdentifier::create([
                            'vessel_id' => $vessel->id,
                            'identifier_type' => 'imo',
                            'identifier_value' => $vessel->imo_number,
                            'is_current' => true,
                        ]);
                    }

                    if (!empty($vessel->current_mmsi)) {
                        VesselIdentifier::create([
                            'vessel_id' => $vessel->id,
                            'identifier_type' => 'mmsi',
                            'identifier_value' => $vessel->current_mmsi,
                            'is_current' => true,
                        ]);
                    }

                    $createdEntities[] = $vessel->id;
                }

                DB::commit();
                $validCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                ImportQuarantinedRecord::create([
                    'data_import_id' => $dataImport->id,
                    'row_index' => $index + 1,
                    'raw_payload' => $row,
                    'quarantine_reason' => QuarantineReason::TYPE_MISMATCH,
                    'error_details' => 'Database exception: ' . $e->getMessage(),
                    'resolution_status' => 'pending_review',
                ]);
                $quarantineCount++;
            }
        }

        $finalStatus = ImportStatus::COMPLETED;
        if ($quarantineCount > 0 && $validCount > 0) {
            $finalStatus = ImportStatus::PARTIALLY_IMPORTED;
        } elseif ($quarantineCount > 0 && $validCount === 0) {
            $finalStatus = ImportStatus::FAILED;
        }

        $dataImport->update([
            'status' => $finalStatus,
            'processed_rows' => count($rows),
            'valid_rows_count' => $validCount,
            'quarantined_rows_count' => $quarantineCount,
            'duplicate_rows_count' => $duplicateCount,
            'summary_report' => [
                'total_ingested' => $validCount,
                'total_quarantined' => $quarantineCount,
                'created_entities_ids' => $createdEntities,
            ],
            'completed_at' => now(),
        ]);

        if ($datasetVersion) {
            $datasetVersion->increment('records_count', $validCount);
        }

        AuditLog::log('execute_import_pipeline', $dataImport, null, [
            'batch_reference' => $batchRef,
            'valid_count' => $validCount,
            'quarantine_count' => $quarantineCount,
        ]);

        return $dataImport;
    }

    /**
     * Resolves a quarantined row
     */
    public function resolveQuarantineRecord(
        ImportQuarantinedRecord $record,
        string $decision, // 'discard', 'whitelist_and_import', 'correct_and_import'
        ?array $correctedPayload = null,
        ?string $notes = null,
        ?User $user = null
    ): ImportQuarantinedRecord {
        if ($decision === 'discard') {
            $record->update([
                'resolution_status' => 'discarded',
                'reviewed_by' => $user?->id,
                'resolution_notes' => $notes ?? 'Discarded by analyst as invalid entry.',
            ]);
            return $record;
        }

        $payload = $correctedPayload ?? $record->raw_payload;
        $import = $record->dataImport;

        $sourceRecord = SourceRecord::create([
            'data_source_id' => $import->data_source_id,
            'record_identifier' => $payload['record_id'] ?? "QUARANTINE-RESOLVED-{$record->id}",
            'record_date' => date('Y-m-d'),
            'verbatim_payload' => $payload,
            'sha256_hash' => hash('sha256', json_encode($payload)),
            'verification_status' => 'verified',
            'analyst_notes' => "Resolved from quarantine by analyst. Notes: {$notes}",
        ]);

        if ($import->target_entity_type === 'vessel') {
            $flagId = $this->resolveFlagCountryId($payload['flag'] ?? $payload['flag_state'] ?? null, $import->country_id);
            $vessel = Vessel::create([
                'current_name' => $payload['name'] ?? $payload['current_name'] ?? $payload['vessel_name'] ?? 'UNKNOWN VESSEL',
                'imo_number' => $payload['imo'] ?? $payload['imo_number'] ?? null,
                'current_mmsi' => $payload['mmsi'] ?? $payload['current_mmsi'] ?? null,
                'current_callsign' => $payload['callsign'] ?? $payload['current_callsign'] ?? null,
                'current_flag_country_id' => $flagId,
                'vessel_type' => $payload['vessel_type'] ?? 'fishing',
                'length_overall' => $payload['length'] ?? $payload['length_overall'] ?? null,
                'gross_tonnage' => $payload['tonnage'] ?? $payload['gross_tonnage'] ?? null,
                'verification_status' => VerificationStatus::VERIFIED,
            ]);

            VesselName::create([
                'vessel_id' => $vessel->id,
                'name' => $vessel->current_name,
                'is_current' => true,
                'valid_from' => now()->toDateString(),
            ]);

            if (!empty($vessel->imo_number)) {
                VesselIdentifier::create([
                    'vessel_id' => $vessel->id,
                    'identifier_type' => 'imo',
                    'identifier_value' => $vessel->imo_number,
                    'is_current' => true,
                ]);
            }
        }

        $record->update([
            'resolution_status' => 'corrected_and_reprocessed',
            'reviewed_by' => $user?->id,
            'resolution_notes' => $notes,
        ]);

        $import->increment('valid_rows_count');
        $import->decrement('quarantined_rows_count');

        return $record;
    }

    protected function resolveFlagCountryId(?string $flag, ?string $defaultCountryId = null): ?string
    {
        if (!empty($defaultCountryId)) {
            return $defaultCountryId;
        }

        if (empty($flag)) {
            return null;
        }

        $country = Country::where('name', 'ilike', $flag)
            ->orWhere('iso_code_3', 'ilike', $flag)
            ->orWhere('iso_code_2', 'ilike', $flag)
            ->first();

        return $country?->id;
    }
}
