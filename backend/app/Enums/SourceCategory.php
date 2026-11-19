<?php

namespace App\Enums;

enum SourceCategory: string
{
    case GOVERNMENT = 'government';
    case INTERNATIONAL_ORG = 'international_org';
    case RESEARCH_INSTITUTION = 'research_institution';
    case ACADEMIC = 'academic';
    case NGO_REPORT = 'ngo_report';
    case NEWS_PUBLICATION = 'news_publication';
    case OPEN_DATASET = 'open_dataset';
    case WHISTLEBLOWER = 'whistleblower';
    case COMMERCIAL_PROVIDER = 'commercial_provider';

    public function label(): string
    {
        return match($this) {
            self::GOVERNMENT => 'Government Maritime Authority / Ministry',
            self::INTERNATIONAL_ORG => 'International Organization / RFMO / UN',
            self::RESEARCH_INSTITUTION => 'Independent Research Institution',
            self::ACADEMIC => 'Peer-Reviewed Academic Publication',
            self::NGO_REPORT => 'Civil Society / NGO Investigation Report',
            self::NEWS_PUBLICATION => 'Investigative Journalism Publication',
            self::OPEN_DATASET => 'Open Public Registry / Dataset',
            self::WHISTLEBLOWER => 'Confidential Whistleblower Source',
            self::COMMERCIAL_PROVIDER => 'Commercial Maritime Intelligence Vendor',
        };
    }
}
