<?php

namespace App\Enums;

enum DatasetStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case DEPRECATED = 'deprecated';
    case SUPERSEDED = 'superseded';
}
