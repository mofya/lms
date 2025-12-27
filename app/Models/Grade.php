<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'quiz_average',
        'assignment_average',
        'participation_score',
        'final_grade',
        'quiz_weight',
        'assignment_weight',
        'participation_weight',
        'total_quizzes',
        'completed_quizzes',
        'total_assignments',
        'completed_assignments',
        'calculated_at',
    ];

    protected $casts = [
        'quiz_average' => 'decimal:2',
        'assignment_average' => 'decimal:2',
        'participation_score' => 'decimal:2',
        'final_grade' => 'decimal:2',
        'quiz_weight' => 'integer',
        'assignment_weight' => 'integer',
        'participation_weight' => 'integer',
        'total_quizzes' => 'integer',
        'completed_quizzes' => 'integer',
        'total_assignments' => 'integer',
        'completed_assignments' => 'integer',
        'calculated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Calculate and update the grade for a user in a course
     */
    public function recalculate(): self
    {
        $course = $this->course;
        $user = $this->user;

        // Calculate quiz average
        $quizAverage = $this->calculateQuizAverage($course, $user);
        
        // Calculate assignment average
        $assignmentAverage = $this->calculateAssignmentAverage($course, $user);
        
        // Calculate participation (based on lesson completion)
        $participationScore = $this->calculateParticipationScore($course, $user);

        // Calculate weighted final grade
        $finalGrade = $this->calculateWeightedGrade(
            $quizAverage,
            $assignmentAverage,
            $participationScore
        );

        // Count totals
        $totalQuizzes = $course->quizzes()->published()->count();
        $completedQuizzes = $course->quizzes()
            ->published()
            ->whereHas('tests', fn($q) => $q->where('user_id', $user->id))
            ->count();

        $totalAssignments = $course->assignments()->where('is_published', true)->count();
        $completedAssignments = $course->assignments()
            ->where('is_published', true)
            ->whereHas('submissions', fn($q) => $q->where('user_id', $user->id)->where('status', '!=', 'draft'))
            ->count();

        $this->update([
            'quiz_average' => $quizAverage,
            'assignment_average' => $assignmentAverage,
            'participation_score' => $participationScore,
            'final_grade' => $finalGrade,
            'total_quizzes' => $totalQuizzes,
            'completed_quizzes' => $completedQuizzes,
            'total_assignments' => $totalAssignments,
            'completed_assignments' => $completedAssignments,
            'calculated_at' => now(),
        ]);

        return $this;
    }

    protected function calculateQuizAverage(Course $course, User $user): ?float
    {
        $tests = Test::where('user_id', $user->id)
            ->whereHas('quiz', fn($q) => $q->where('course_id', $course->id)->where('is_published', true))
            ->get();

        if ($tests->isEmpty()) {
            return null;
        }

        // Get best attempt per quiz
        $bestScores = [];
        foreach ($tests as $test) {
            $quizId = $test->quiz_id;
            $score = $test->score;
            $totalQuestions = $test->quiz->questions()->count();

            if ($totalQuestions > 0) {
                $percentage = ($score / $totalQuestions) * 100;
                
                if (!isset($bestScores[$quizId]) || $percentage > $bestScores[$quizId]) {
                    $bestScores[$quizId] = $percentage;
                }
            }
        }

        if (empty($bestScores)) {
            return null;
        }

        return round(array_sum($bestScores) / count($bestScores), 2);
    }

    protected function calculateAssignmentAverage(Course $course, User $user): ?float
    {
        $submissions = AssignmentSubmission::where('user_id', $user->id)
            ->whereHas('assignment', fn($q) => $q->where('course_id', $course->id)->where('is_published', true))
            ->where('status', '!=', 'draft')
            ->with('grade')
            ->get();

        if ($submissions->isEmpty()) {
            return null;
        }

        // Get best submission per assignment
        $bestScores = [];
        foreach ($submissions as $submission) {
            $assignmentId = $submission->assignment_id;
            $finalScore = $submission->getFinalScore();
            $maxPoints = $submission->assignment->max_points;

            if ($finalScore !== null && $maxPoints > 0) {
                $percentage = ($finalScore / $maxPoints) * 100;
                
                if (!isset($bestScores[$assignmentId]) || $percentage > $bestScores[$assignmentId]) {
                    $bestScores[$assignmentId] = $percentage;
                }
            }
        }

        if (empty($bestScores)) {
            return null;
        }

        return round(array_sum($bestScores) / count($bestScores), 2);
    }

    protected function calculateParticipationScore(Course $course, User $user): ?float
    {
        $totalLessons = $course->publishedLessons()->count();
        
        if ($totalLessons === 0) {
            return null;
        }

        $completedLessons = $user->completedLessons()
            ->where('course_id', $course->id)
            ->count();

        return round(($completedLessons / $totalLessons) * 100, 2);
    }

    protected function calculateWeightedGrade(?float $quizAvg, ?float $assignmentAvg, ?float $participation): ?float
    {
        $totalWeight = $this->quiz_weight + $this->assignment_weight + $this->participation_weight;
        
        if ($totalWeight === 0) {
            return null;
        }

        $weightedSum = 0;
        $usedWeight = 0;

        if ($quizAvg !== null) {
            $weightedSum += ($quizAvg * $this->quiz_weight);
            $usedWeight += $this->quiz_weight;
        }

        if ($assignmentAvg !== null) {
            $weightedSum += ($assignmentAvg * $this->assignment_weight);
            $usedWeight += $this->assignment_weight;
        }

        if ($participation !== null) {
            $weightedSum += ($participation * $this->participation_weight);
            $usedWeight += $this->participation_weight;
        }

        if ($usedWeight === 0) {
            return null;
        }

        // Normalize if some components are missing
        $normalizedWeight = $usedWeight / $totalWeight;
        $finalGrade = $weightedSum / ($normalizedWeight * $totalWeight);

        return round($finalGrade, 2);
    }

    /**
     * Get or create a grade record for a user in a course
     */
    public static function getOrCreateForUserCourse(User $user, Course $course): self
    {
        $grade = self::firstOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id,
            ],
            [
                'quiz_weight' => 40,
                'assignment_weight' => 50,
                'participation_weight' => 10,
            ]
        );

        return $grade;
    }
}
