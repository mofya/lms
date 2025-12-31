<?php

namespace App\Enums;

enum NavigatorPosition: string
{
    case Bottom = 'bottom';
    case Left = 'left';
    case Right = 'right';
    case Top = 'top';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Bottom => 'Bottom',
            self::Left => 'Left Sidebar',
            self::Right => 'Right Sidebar',
            self::Top => 'Top',
            self::Hidden => 'Hidden',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
