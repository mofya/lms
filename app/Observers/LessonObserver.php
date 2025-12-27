<?php

namespace App\Observers;

use App\Models\Lesson;
use Illuminate\Support\Facades\DB;

class LessonObserver
{
    public function creating(Lesson $lesson)
    {
        $lesson->position = (int)Lesson::where('course_id', $lesson->course_id)
                ->max('position') + 1;
    }

    public function deleted(Lesson $lesson)
    {
        $orderColumn = 'position';
        $keyName = $lesson->getKeyName();
        $ordered = Lesson::where('course_id', $lesson->course_id)
            ->orderBy('position')
            ->pluck('id');
        $cases = collect($ordered)
            ->map(fn($key, int $index) => sprintf(
                'when %s = %s then %d',
                $keyName,
                DB::getPdo()->quote($key),
                $index + 1
            ))
            ->implode(' ');

        Lesson::query()
            ->whereIn('id', $ordered)
            ->update([
                $orderColumn => DB::raw('case ' . $cases . ' end'),
            ]);
    }
}
