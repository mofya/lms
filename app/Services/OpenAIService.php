<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIService
{
    public function generateQuestions(string $prompt)
    {
        $fullPrompt = $prompt . "\n\nPlease output the result as JSON with the following structure:
        [
            {
                \"question_text\": \"What is the capital of France?\",
                \"answer_explanation\": \"Paris is the capital of France.\",
                \"more_info_link\": \"https://en.wikipedia.org/wiki/Paris\",
                \"options\": [
                    { \"option\": \"Paris\", \"correct\": true },
                    { \"option\": \"London\", \"correct\": false },
                    { \"option\": \"Berlin\", \"correct\": false },
                    { \"option\": \"Madrid\", \"correct\": false }
                ]
            },
            ...
        ]";

        $response = Http::withToken(config('services.openai.api_key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'user', 'content' => $fullPrompt]
                ],
            ]);

        if ($response->failed()) {
            return $response->json('error.message') ?? 'An error occurred.';
        }

        $content = $response->json('choices.0.message.content');
        $parsed = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return 'Failed to parse OpenAI response. Response was: ' . $content;
        }

        return $parsed;
    }
}