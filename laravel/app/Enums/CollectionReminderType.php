<?php

namespace App\Enums;

enum CollectionReminderType: string
{
    case BeforeDue7 = 'before_due_7';
    case OnDue = 'on_due';
    case Overdue3 = 'overdue_3';
    case Overdue7 = 'overdue_7';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::BeforeDue7 => '7 days before due',
            self::OnDue => 'Due today',
            self::Overdue3 => '3 days overdue',
            self::Overdue7 => '7 days overdue',
            self::Manual => 'Manual reminder',
        };
    }

    /**
     * Days from due_date when this automated reminder fires (negative = before due).
     */
    public function dayOffset(): ?int
    {
        return match ($this) {
            self::BeforeDue7 => -7,
            self::OnDue => 0,
            self::Overdue3 => 3,
            self::Overdue7 => 7,
            self::Manual => null,
        };
    }

    /**
     * @return list<self>
     */
    public static function automated(): array
    {
        return [
            self::BeforeDue7,
            self::OnDue,
            self::Overdue3,
            self::Overdue7,
        ];
    }
}
