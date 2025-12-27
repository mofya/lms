<?php

namespace App\Providers;

use App\Models\Test;
use App\Models\AssignmentSubmission;
use App\Models\Announcement;
use App\Models\Quiz;
use App\Observers\GradeObserver;
use App\Observers\AnnouncementObserver;
use App\Observers\QuizObserver;
use App\Observers\AssignmentSubmissionNotificationObserver;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Preserve Filament v3 layout spanning behavior globally.
        Fieldset::configureUsing(fn (Fieldset $fieldset) => $fieldset->columnSpanFull());
        Grid::configureUsing(fn (Grid $grid) => $grid->columnSpanFull());
        Section::configureUsing(fn (Section $section) => $section->columnSpanFull());

        // Register observers for grade calculation
        Test::observe(GradeObserver::class);
        AssignmentSubmission::observe(GradeObserver::class);
        
        // Register observers for notifications
        Announcement::observe(AnnouncementObserver::class);
        Quiz::observe(QuizObserver::class);
        AssignmentSubmission::observe(AssignmentSubmissionNotificationObserver::class);
    }
}
