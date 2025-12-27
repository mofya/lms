<?php

namespace App\Services;

use App\Models\AssignmentSubmission;
use App\Models\SubmissionGrade;
use Illuminate\Support\Facades\Http;

class LlmAssignmentGrader
{
    public const PROVIDERS = ['openai', 'anthropic', 'gemini'];

    public function gradeSubmission(AssignmentSubmission $submission, string $provider = 'openai'): bool
    {
        $promptBuilder = new AssignmentGradingPromptBuilder();
        $prompt = $promptBuilder->build($submission);

        // Update submission status
        $submission->update(['status' => 'grading']);

        $result = match ($provider) {
            'openai' => $this->callOpenAI($prompt),
            'anthropic' => $this->callAnthropic($prompt),
            'gemini' => $this->callGemini($prompt),
            default => null,
        };

        if (!$result || is_string($result)) {
            $submission->update(['status' => 'submitted']);
            return false;
        }

        // Save the grade
        $grade = $submission->grade ?? $submission->grade()->make();
        $grade->fill([
            'ai_score' => $result['score'] ?? null,
            'ai_feedback' => $result['feedback'] ?? null,
            'ai_criteria_scores' => $result['criteria_scores'] ?? null,
            'ai_provider' => $provider,
            'ai_graded_at' => now(),
            'approval_status' => 'pending',
        ]);
        $grade->save();

        $submission->update(['status' => 'graded']);

        return true;
    }

    public function batchGrade(iterable $submissions, string $provider = 'openai'): void
    {
        foreach ($submissions as $submission) {
            $this->gradeSubmission($submission, $provider);
        }
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
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

        if ($response->failed()) {
            return $response->json('error.message') ?? 'OpenAI request failed.';
        }

        $content = $response->json('choices.0.message.content');
        return $this->parseJsonString($content, 'OpenAI');
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
            'max_tokens' => 2000,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if ($response->failed()) {
            return $response->json('error.message') ?? 'Anthropic request failed.';
        }

        $content = $response->json('content.0.text');
        return $this->parseJsonString($content, 'Anthropic');
    }

    protected function callGemini(string $prompt): array|string
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-pro');

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

        $content = $response->json('candidates.0.content.parts.0.text');
        return $this->parseJsonString($content, 'Gemini');
    }

    protected function parseJsonString(?string $content, string $provider): array|string
    {
        if (blank($content)) {
            return "{$provider} returned empty response.";
        }

        // Try to extract JSON from markdown code blocks
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return "Failed to parse {$provider} response as JSON. Response: " . substr($content, 0, 200);
        }

        return $decoded;
    }
}
