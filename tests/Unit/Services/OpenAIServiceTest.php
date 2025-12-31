<?php

namespace Tests\Unit\Services;

use App\Services\OpenAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAIServiceTest extends TestCase
{
    use RefreshDatabase;

    private OpenAIService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OpenAIService;
    }

    public function test_generate_questions_calls_openai_api(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                [
                                    'question_text' => 'What is the capital of France?',
                                    'answer_explanation' => 'Paris is the capital of France.',
                                    'options' => [
                                        ['option' => 'Paris', 'correct' => true],
                                        ['option' => 'London', 'correct' => false],
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $result = $this->service->generateQuestions('Generate geography questions');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('What is the capital of France?', $result[0]['question_text']);
    }

    public function test_generate_questions_includes_json_schema_in_prompt(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => function ($request) {
                $content = $request->data()['messages'][0]['content'];

                // Verify the prompt includes expected structure
                $this->assertStringContainsString('question_text', $content);
                $this->assertStringContainsString('answer_explanation', $content);
                $this->assertStringContainsString('options', $content);

                return Http::response([
                    'choices' => [
                        ['message' => ['content' => '[]']],
                    ],
                ]);
            },
        ]);

        $this->service->generateQuestions('Test prompt');
    }

    public function test_generate_questions_uses_gpt4_model(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => function ($request) {
                $this->assertEquals('gpt-4', $request->data()['model']);

                return Http::response([
                    'choices' => [
                        ['message' => ['content' => '[]']],
                    ],
                ]);
            },
        ]);

        $this->service->generateQuestions('Test prompt');
    }

    public function test_generate_questions_handles_api_failure(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'error' => ['message' => 'Invalid API key'],
            ], 401),
        ]);

        $result = $this->service->generateQuestions('Test prompt');

        $this->assertEquals('Invalid API key', $result);
    }

    public function test_generate_questions_handles_generic_api_failure(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([], 500),
        ]);

        $result = $this->service->generateQuestions('Test prompt');

        $this->assertEquals('An error occurred.', $result);
    }

    public function test_generate_questions_handles_invalid_json_response(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'This is not valid JSON']],
                ],
            ]),
        ]);

        $result = $this->service->generateQuestions('Test prompt');

        $this->assertIsString($result);
        $this->assertStringContainsString('Failed to parse', $result);
    }

    public function test_generate_questions_parses_valid_json_array(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        $questions = [
            [
                'question_text' => 'Question 1',
                'options' => [],
            ],
            [
                'question_text' => 'Question 2',
                'options' => [],
            ],
        ];

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode($questions)]],
                ],
            ]),
        ]);

        $result = $this->service->generateQuestions('Generate 2 questions');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function test_generate_questions_sends_correct_request_structure(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => function ($request) {
                $data = $request->data();

                $this->assertArrayHasKey('model', $data);
                $this->assertArrayHasKey('messages', $data);
                $this->assertCount(1, $data['messages']);
                $this->assertEquals('user', $data['messages'][0]['role']);

                return Http::response([
                    'choices' => [
                        ['message' => ['content' => '[]']],
                    ],
                ]);
            },
        ]);

        $this->service->generateQuestions('Test');
    }
}
