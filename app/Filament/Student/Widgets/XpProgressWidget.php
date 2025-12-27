<?php

namespace App\Filament\Student\Widgets;

use App\Services\XpService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class XpProgressWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();
        $xpService = new XpService();
        $xpForNextLevel = $xpService->getXpForNextLevel($user);
        $progress = $xpService->getLevelProgress($user);

        return [
            Stat::make('Level', $user->level)
                ->description('Current level')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success'),
            
            Stat::make('XP Points', number_format($user->xp_points))
                ->description("{$xpForNextLevel} XP to next level")
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('info'),
            
            Stat::make('Current Streak', $user->current_streak ? "{$user->current_streak} days" : 'No streak')
                ->description('Consecutive days active')
                ->descriptionIcon('heroicon-m-fire')
                ->color('warning'),
        ];
    }
}
