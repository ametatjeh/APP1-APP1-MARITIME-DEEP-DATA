<?php

namespace App\Enums;

enum QuarantineReason: string
{
    case INVALID_IMO_CHECKSUM = 'invalid_imo_checksum';
    case INVALID_MMSI_LENGTH = 'invalid_mmsi_length';
    case INVALID_CALLSIGN_FORMAT = 'invalid_callsign_format';
    case MISSING_REQUIRED_FIELDS = 'missing_required_fields';
    case DUPLICATE_RECORD = 'duplicate_record';
    case TYPE_MISMATCH = 'type_mismatch';
    case SCHEMA_ERROR = 'schema_error';
}
