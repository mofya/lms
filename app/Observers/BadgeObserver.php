<?php

namespace App\Observers;

use App\Models\Badge;
use App\Models\User;

class BadgeObserver
{
    /**
     * Check and award badges for a user
     */
    public static function checkBadges(User $user): void
    {
        $badges = Badge::where('is_active', true)->get();
        
        foreach ($badges as $badge) {
            $badge->checkAndAward($user);
        }
    }
}
