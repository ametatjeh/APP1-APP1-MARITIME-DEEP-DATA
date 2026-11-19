<?php

namespace App\Services;

class DataValidationService
{
    /**
     * Common ITU Maritime Identification Digits (MID) mapped to flag states
     */
    protected static array $midCountries = [
        '525' => 'Indonesia',
        '351' => 'Panama',
        '352' => 'Panama',
        '353' => 'Panama',
        '354' => 'Panama',
        '355' => 'Panama',
        '356' => 'Panama',
        '357' => 'Panama',
        '370' => 'Panama',
        '371' => 'Panama',
        '372' => 'Panama',
        '373' => 'Panama',
        '374' => 'Panama',
        '412' => 'China',
        '413' => 'China',
        '414' => 'China',
        '273' => 'Russia',
        '576' => 'Vanuatu',
        '577' => 'Vanuatu',
        '538' => 'Marshall Islands',
        '636' => 'Liberia',
        '232' => 'United Kingdom',
        '366' => 'United States',
        '367' => 'United States',
        '368' => 'United States',
        '369' => 'United States',
        '548' => 'Philippines',
        '567' => 'Thailand',
        '574' => 'Vietnam',
    ];

    /**
     * Validates official IMO 7-digit identifier using modulo-10 weighted checksum formula
     */
    public function validateImo(string|null $rawImo): array
    {
        if (empty($rawImo)) {
            return [
                'is_valid' => false,
                'reason' => 'IMO number is missing or empty.',
                'cleaned_imo' => null,
            ];
        }

        // Strip non-digits and optional 'IMO' prefix
        $digits = preg_replace('/\D/', '', $rawImo);

        if (strlen($digits) !== 7) {
            return [
                'is_valid' => false,
                'reason' => "IMO number must be exactly 7 numeric digits (received: '{$digits}', length: " . strlen($digits) . ").",
                'cleaned_imo' => $digits,
            ];
        }

        // IMO algorithm: sum of first 6 digits * (8 - position)
        // Position 1 * 7 + Position 2 * 6 + ... + Position 6 * 2
        $sum = 0;
        for ($i = 0; $i < 6; $i++) {
            $weight = 8 - ($i + 1);
            $sum += (int)$digits[$i] * $weight;
        }

        $expectedCheckDigit = $sum % 10;
        $actualCheckDigit = (int)$digits[6];

        if ($expectedCheckDigit !== $actualCheckDigit) {
            return [
                'is_valid' => false,
                'reason' => "Invalid IMO checksum. Expected 7th digit: {$expectedCheckDigit}, but found: {$actualCheckDigit}.",
                'cleaned_imo' => $digits,
                'expected_check_digit' => $expectedCheckDigit,
                'actual_check_digit' => $actualCheckDigit,
            ];
        }

        return [
            'is_valid' => true,
            'reason' => 'IMO checksum verified successfully.',
            'cleaned_imo' => $digits,
        ];
    }

    /**
     * Validates 9-digit MMSI and maps ITU Maritime Identification Digits (MID)
     */
    public function validateMmsi(string|null $rawMmsi): array
    {
        if (empty($rawMmsi)) {
            return [
                'is_valid' => false,
                'reason' => 'MMSI is missing or empty.',
                'cleaned_mmsi' => null,
                'country_name' => null,
            ];
        }

        $digits = preg_replace('/\D/', '', $rawMmsi);

        if (strlen($digits) !== 9) {
            return [
                'is_valid' => false,
                'reason' => "MMSI must be exactly 9 numeric digits (received: '{$digits}', length: " . strlen($digits) . ").",
                'cleaned_mmsi' => $digits,
                'country_name' => null,
            ];
        }

        $mid = substr($digits, 0, 3);
        $country = self::$midCountries[$mid] ?? 'Unknown / Other Maritime Administration';

        return [
            'is_valid' => true,
            'reason' => 'MMSI format valid.',
            'cleaned_mmsi' => $digits,
            'mid' => $mid,
            'country_name' => $country,
        ];
    }

    /**
     * Validates international radio callsign format
     */
    public function validateCallsign(string|null $callsign): array
    {
        if (empty($callsign)) {
            return [
                'is_valid' => false,
                'reason' => 'Callsign is missing.',
                'cleaned_callsign' => null,
            ];
        }

        $cleaned = strtoupper(trim($callsign));

        if (!preg_match('/^[A-Z0-9]{3,10}$/', $cleaned)) {
            return [
                'is_valid' => false,
                'reason' => "Callsign must be 3-10 alphanumeric characters (received: '{$cleaned}').",
                'cleaned_callsign' => $cleaned,
            ];
        }

        return [
            'is_valid' => true,
            'reason' => 'Callsign format valid.',
            'cleaned_callsign' => $cleaned,
        ];
    }

    /**
     * Full row validator for import staging
     */
    public function validateRow(array $row, string $targetType, array $rules = []): array
    {
        $errors = [];

        if ($targetType === 'vessel') {
            if (empty($row['name']) && empty($row['vessel_name'])) {
                $errors[] = 'Missing vessel name.';
            }

            // IMO Checksum validation (if present or enforced)
            $imo = $row['imo'] ?? $row['imo_number'] ?? null;
            if (!empty($imo)) {
                $imoCheck = $this->validateImo((string)$imo);
                if (!$imoCheck['is_valid']) {
                    $errors[] = $imoCheck['reason'];
                }
            } elseif (!empty($rules['enforce_imo'])) {
                $errors[] = 'Mandatory IMO number is missing.';
            }

            // MMSI validation
            $mmsi = $row['mmsi'] ?? null;
            if (!empty($mmsi)) {
                $mmsiCheck = $this->validateMmsi((string)$mmsi);
                if (!$mmsiCheck['is_valid']) {
                    $errors[] = $mmsiCheck['reason'];
                }
            }
        } elseif ($targetType === 'person') {
            if (empty($row['full_name']) && empty($row['name'])) {
                $errors[] = 'Missing person legal name.';
            }
        } elseif ($targetType === 'company') {
            if (empty($row['company_name']) && empty($row['name'])) {
                $errors[] = 'Missing corporate name.';
            }
        }

        if (count($errors) > 0) {
            return [
                'is_valid' => false,
                'errors' => $errors,
                'error_message' => implode(' | ', $errors),
            ];
        }

        return [
            'is_valid' => true,
            'errors' => [],
            'error_message' => null,
        ];
    }
}
