<?php

namespace App\Enums;

enum ImportStatus: string
{
    case PENDING = 'pending';
    case VALIDATING = 'validating';
    case IMPORTING = 'importing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case ROLLED_BACK = 'rolled_back';
    case PARTIALLY_IMPORTED = 'partially_imported';
}
