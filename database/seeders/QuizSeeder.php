<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    private array $quizData = [
        'title' => 'PHP Knowledge',
        'questions' => [
            [
                'question_text' => 'Which PHP version is the latest version?',
                'more_info_link' => 'https://php.net',
                'answers' => [
                    ['option' => '8.0', 'correct' => false],
                    ['option' => '8.1', 'correct' => false],
                    ['option' => '8.2', 'correct' => false],
                    ['option' => '8.3', 'correct' => false],
                    ['option' => '8.4', 'correct' => true],
                ]
            ],
            [
                'question_text' => 'What does PHP stand for?',
                'answers' => [
                    ['option' => 'Personal Home Page', 'correct' => false],
                    ['option' => 'PHP: Hypertext Preprocessor', 'correct' => true],
                    ['option' => 'Professional Hypertext Parser', 'correct' => false],
                    ['option' => 'Programming Hypertext Processor', 'correct' => false],
                ]
            ],
            [
                'question_text' => 'Which of the following is a valid way to declare a variable in PHP?',
                'answers' => [
                    ['option' => '$variableName = "value";', 'correct' => true],
                    ['option' => 'var variableName = "value";', 'correct' => false],
                    ['option' => 'variableName := "value";', 'correct' => false],
                    ['option' => 'let variableName = "value";', 'correct' => false],
                ]
            ],
            [
                'question_text' => 'What will be the output of the following PHP code?',
                'code_snippet' => '<?php
$x = 10;
$y = "10";

if ($x === $y) {
    echo "Equal";
} else {
    echo "Not Equal";
}
?>',
                'answers' => [
                    ['option' => 'Equal', 'correct' => false],
                    ['option' => 'Not Equal', 'correct' => true],
                    ['option' => 'Error', 'correct' => false],
                    ['option' => 'Warning', 'correct' => false],
                ]
            ]
        ]
    ];

    public function run(): void
    {
        // Ensure a course exists or create a default one
        $course = \App\Models\Course::first();
        if (!$course) {
            $course = \App\Models\Course::create([
                'title' => 'Default Course',
                'description' => 'This is a default course for quizzes.',
                'is_published' => true,
            ]);
        }

        // Create the quiz and associate it with the course
        $quiz = Quiz::create([
            'title' => $this->quizData['title'],
            'is_published' => true,
            'course_id' => $course->id, // ✅ Assign the course ID
        ]);

        $questionIds = [];

        foreach ($this->quizData['questions'] as $questionData) {
            $question = Question::create(
                collect($questionData)
                    ->except('answers')
                    ->toArray()
            );

            foreach ($questionData['answers'] as $answer) {
                QuestionOption::create([
                    'option'      => $answer['option'],
                    'correct'     => $answer['correct'],
                    'question_id' => $question->id,
                ]);
            }

            $questionIds[] = $question->id;
        }

        $quiz->questions()->sync($questionIds);
    }
}
