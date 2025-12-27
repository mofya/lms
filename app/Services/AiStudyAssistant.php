<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Facades\Http;

class AiStudyAssistant
{
    public const PROVIDERS = ['openai', 'anthropic', 'gemini'];

    /**
     * Get AI response to student question with course context
     */
    public function askQuestion(string $question, ?Course $course = null, string $provider = 'openai'): array|string
    {
        $prompt = $this->buildPrompt($question, $course);
        
        return match ($provider) {
            'openai' => $this->callOpenAI($prompt),
            'anthropic' => $this->callAnthropic($prompt),
            'gemini' => $this->callGemini($prompt),
            default => 'Unsupported provider selected.',
        };
    }

    protected function buildPrompt(string $question, ?Course $course): string
    {
        $prompt = "You are an AI study assistant helping a student with their coursework.\n\n";
        
        if ($course) {
            $prompt .= "COURSE CONTEXT:\n";
            $prompt .= "Course: {$course->title}\n";
            
            if ($course->description) {
                $description = is_array($course->description) 
                    ? strip_tags(json_encode($course->description))
                    : strip_tags($course->description);
                $prompt .= "Description: " . substr($description, 0, 500) . "\n";
            }

            // Include lesson content summaries
            $lessons = $course->publishedLessons()->limit(10)->get();
            if ($lessons->isNotEmpty()) {
                $prompt .= "\nRelevant Course Content:\n";
                foreach ($lessons as $lesson) {
                    $content = strip_tags($lesson->lesson_text ?? '');
                    $prompt .= "- {$lesson->title}: " . substr($content, 0, 200) . "...\n";
                }
            }

            // Include quiz topics
            $quizzes = $course->quizzes()->published()->with('questions')->limit(5)->get();
            if ($quizzes->isNotEmpty()) {
                $prompt .= "\nQuiz Topics Covered:\n";
                foreach ($quizzes as $quiz) {
                    $prompt .= "- {$quiz->title}\n";
                }
            }
        }

        $prompt .= "\n\nSTUDENT QUESTION:\n";
        $prompt .= $question;
        
        $prompt .= "\n\nPlease provide a helpful, educational response. If the question is about course content, reference the relevant materials. Be concise but thorough.";

        return $prompt;
    }

    protected function callOpenAI(string $prompt): array|string
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model', 'gpt-4o-mini');

        if (blank($apiKey)) {
            return 'OpenAI API key is missing.';
        }

        $response = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful study assistant. Provide clear, educational explanations.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 1000,
            ]);

        if ($response->failed()) {
            return $response->json('error.message') ?? 'OpenAI request failed.';
        }

        return $response->json('choices.0.message.content') ?? 'No response received.';
    }

    protected function callAnthropic(string $prompt): array|string
    {
        $apiKey = config('services.anthropic.api_key');
        $model = config('services.anthropic.model', 'claude-3-5-sonnet-latest');

        if (blank($apiKey)) {
            return 'Anthropic API key is missing.';
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 1000,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if ($response->failed()) {
            return $response->json('error.message') ?? 'Anthropic request failed.';
        }

        return $response->json('content.0.text') ?? 'No response received.';
    }

    protected function callGemini(string $prompt): array|string
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-1.5-flash');

        if (blank($apiKey)) {
            return 'Gemini API key is missing.';
        }

        $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ]);

        if ($response->failed()) {
            return $response->json('error.message') ?? 'Gemini request failed.';
        }

        return $response->json('candidates.0.content.parts.0.text') ?? 'No response received.';
    }
}
