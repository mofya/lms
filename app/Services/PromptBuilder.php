<?php

namespace App\Services;

use App\Models\Course;

class PromptBuilder
{
    /**
    * Build a detailed prompt for LLM-based quiz question generation.
    *
    * @param  array  $params Structured inputs from the generator form.
    */
    public function build(array $params): string
    {
        $courseContext = $this->buildCourseContext($params['course_id'] ?? null);
        $topic = trim($params['topic'] ?? '');
        $difficulty = $this->normalizeDifficulty($params['difficulty'] ?? 'mixed');
        $difficultyDescription = $this->difficultyDescription($difficulty);
        $numQuestions = max(1, min((int) ($params['num_questions'] ?? 5), 50));
        $questionTypeLines = $this->buildQuestionTypeLines($params['question_types'] ?? []);
        $includeCode = (bool) ($params['include_code_snippets'] ?? false);
        $additional = trim((string) ($params['additional_instructions'] ?? ''));

        $codeNote = $includeCode
            ? "- If relevant, include concise code snippets in questions or explanations. Keep them short and correct."
            : "- Do not include code snippets unless absolutely necessary.";

        $jsonSchema = <<<'JSON'
[
  {
    "question_text": "What is dependency injection?",
    "answer_explanation": "It is a design pattern where dependencies are supplied by an external entity.",
    "more_info_link": "https://example.com/dependency-injection",
    "type": "multiple_choice",
    "correct_answer": null,
    "options": [
      { "option": "It is a way to provide dependencies to a class", "correct": true },
      { "option": "It is a way to compile code faster", "correct": false },
      { "option": "It is a type of runtime error", "correct": false },
      { "option": "It is a testing framework", "correct": false }
    ]
  }
]
JSON;

        return implode("\n", array_filter([
            "You are an expert educator creating quiz questions.",
            $courseContext,
            $topic !== '' ? "Topic or keywords: {$topic}" : null,
            "Total questions: {$numQuestions}",
            "Difficulty: {$difficulty} (write questions appropriate for {$difficultyDescription})",
            "Question type distribution:",
            $questionTypeLines ?: "- Let the model choose a suitable mix.",
            $codeNote,
            $additional !== '' ? "Additional instructions: {$additional}" : null,
            "Output valid JSON ONLY following this schema:",
            $jsonSchema,
            "Respond with JSON only; no commentary, no markdown.",
        ]));
    }

    protected function buildCourseContext(?int $courseId): string
    {
        if (! $courseId) {
            return "Course context not provided.";
        }

        $course = Course::find($courseId);

        if (! $course) {
            return "Course context not provided.";
        }

        $description = $this->flattenDescription($course->description);

        return "Course: {$course->title}" . ($description ? " | Description: {$description}" : '');
    }

    protected function flattenDescription($description): string
    {
        if (is_array($description)) {
            $description = json_encode($description);
        }

        $text = trim(strip_tags((string) $description));

        return $text;
    }

    protected function normalizeDifficulty(string $difficulty): string
    {
        return match (strtolower($difficulty)) {
            'easy', 'beginner' => 'easy',
            'medium', 'intermediate' => 'medium',
            'hard', 'advanced' => 'hard',
            default => 'mixed',
        };
    }

    protected function difficultyDescription(string $difficulty): string
    {
        return match ($difficulty) {
            'easy' => 'beginners; keep questions straightforward and scaffolded',
            'medium' => 'intermediate learners; require application of concepts',
            'hard' => 'advanced learners; emphasize analysis and multi-step reasoning',
            default => 'a balanced mix from easy to hard',
        };
    }

    protected function buildQuestionTypeLines(array $questionTypes): string
    {
        if (empty($questionTypes)) {
            return '';
        }

        $lines = [];

        foreach ($questionTypes as $item) {
            $type = $item['type'] ?? null;
            $count = isset($item['count']) ? (int) $item['count'] : null;

            if (! $type || ! $count) {
                continue;
            }

            $lines[] = "- {$count} {$this->formatType($type)}";
        }

        return implode("\n", $lines);
    }

    protected function formatType(string $type): string
    {
        return match ($type) {
            'multiple_choice' => 'multiple choice (4 options, 1 correct)',
            'checkbox' => 'checkbox (multiple correct)',
            'single_answer' => 'single short answer',
            default => $type,
        };
    }
}

