<?php

namespace App\Enums;

enum DelinquencyEscalationStage: string
{
    case FirstNotice = 'first_notice';
    case SecondNotice = 'second_notice';
    case LegalHandoff = 'legal_handoff';

    public function label(): string
    {
        return match ($this) {
            self::FirstNotice => '1st notice',
            self::SecondNotice => '2nd notice',
            self::LegalHandoff => 'Legal handoff',
        };
    }
}
