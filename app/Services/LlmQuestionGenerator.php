<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LlmQuestionGenerator
{
    public const PROVIDERS = ['openai', 'anthropic', 'gemini'];

    /**
     * @param  array|string  $params Structured params (preferred) or legacy raw prompt string.
     */
    public function generateQuestions(array|string $params, ?string $provider = 'openai')
    {
        $provider = $this->normalizeProvider($params, $provider);
        $prompt = $this->buildPrompt($params);

        return match ($provider) {
            'openai' => $this->callOpenAI($prompt),
            'anthropic' => $this->callAnthropic($prompt),
            'gemini' => $this->callGemini($prompt),
            default => 'Unsupported provider selected.',
        };
    }

    /**
     * Accept structured params or a raw string prompt (legacy support).
     */
    protected function buildPrompt(array|string $params): string
    {
        if (is_string($params)) {
            // Legacy free-text fallback.
            return (new PromptBuilder())->build([
                'topic' => $params,
                'num_questions' => 10,
                'difficulty' => 'mixed',
                'question_types' => [],
                'include_code_snippets' => false,
                'additional_instructions' => '',
            ]);
        }

        return (new PromptBuilder())->build($params);
    }

    protected function normalizeProvider(array|string $params, ?string $provider): string
    {
        if (is_array($params) && isset($params['provider'])) {
            return strtolower((string) $params['provider']);
        }

        return strtolower($provider ?? 'openai');
    }

    protected function callOpenAI(string $prompt)
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
            ]);

        if ($response->failed()) {
            return $response->json('error.message') ?? 'OpenAI request failed.';
        }

        $content = $response->json('choices.0.message.content');

        return $this->parseJsonString($content, 'OpenAI');
    }

    protected function callAnthropic(string $prompt)
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
            'max_tokens' => 1500,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if ($response->failed()) {
            return $response->json('error.message') ?? 'Anthropic request failed.';
        }

        $text = $response->json('content.0.text');

        return $this->parseJsonString($text, 'Anthropic');
    }

    protected function callGemini(string $prompt)
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-1.5-flash');

        if (blank($apiKey)) {
            return 'Gemini API key is missing.';
        }

        $endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $model,
            $apiKey
        );

        $response = Http::post($endpoint, [
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

        $text = $response->json('candidates.0.content.parts.0.text');

        return $this->parseJsonString($text, 'Gemini');
    }

    protected function parseJsonString(?string $content, string $provider)
    {
        if (! is_string($content) || trim($content) === '') {
            return "{$provider} returned an empty response.";
        }

        $parsed = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return "Failed to parse {$provider} response. Response was: {$content}";
        }

        return $parsed;
    }
}

