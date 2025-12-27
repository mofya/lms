<?php

namespace App\Filament\Student\Widgets;

use App\Models\Badge;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class BadgesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = Auth::user();
        $totalBadges = Badge::where('is_active', true)->count();
        $earnedBadges = $user->badges()->count();

        return [
            Stat::make('Badges Earned', $earnedBadges)
                ->description("out of {$totalBadges} available")
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success'),
        ];
    }
}
