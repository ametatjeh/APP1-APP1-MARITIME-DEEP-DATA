<?php

namespace App\Enums;

enum LabourIndicatorType: string
{
    case ABUSE_OF_VULNERABILITY = 'abuse_of_vulnerability';
    case DECEPTION = 'deception';
    case RESTRICTION_OF_MOVEMENT = 'restriction_of_movement';
    case ISOLATION_TRANSSHIPMENT = 'isolation_transshipment';
    case VIOLENCE_INTIMIDATION = 'violence_intimidation';
    case RETENTION_OF_DOCUMENTS = 'retention_of_documents';
    case WITHHOLDING_OF_WAGES = 'withholding_of_wages';
    case DEBT_BONDAGE = 'debt_bondage';
    case ABUSIVE_CONDITIONS = 'abusive_conditions';
    case EXCESSIVE_OVERTIME = 'excessive_overtime';
    case OTHER = 'other';
}
