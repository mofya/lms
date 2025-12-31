<?php

namespace App\Enums;

enum FeedbackTiming: string
{
    case Immediate = 'immediate';
    case AfterSubmit = 'after_submit';
    case AfterDeadline = 'after_deadline';
    case Never = 'never';

    public function label(): string
    {
        return match ($this) {
            self::Immediate => 'Immediately after each answer',
            self::AfterSubmit => 'After quiz submission',
            self::AfterDeadline => 'After deadline passes',
            self::Never => 'Never show feedback',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
