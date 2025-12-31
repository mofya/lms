<?php

namespace Tests\Unit\Services;

use App\Services\LlmQuestionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LlmQuestionGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private LlmQuestionGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new LlmQuestionGenerator;
    }

    public function test_providers_constant_contains_expected_values(): void
    {
        $this->assertContains('openai', LlmQuestionGenerator::PROVIDERS);
        $this->assertContains('anthropic', LlmQuestionGenerator::PROVIDERS);
        $this->assertContains('gemini', LlmQuestionGenerator::PROVIDERS);
    }

    public function test_generate_questions_returns_error_when_openai_key_missing(): void
    {
        config(['services.openai.api_key' => null]);

        $result = $this->generator->generateQuestions(['topic' => 'PHP'], 'openai');

        $this->assertEquals('OpenAI API key is missing.', $result);
    }

    public function test_generate_questions_returns_error_when_anthropic_key_missing(): void
    {
        config(['services.anthropic.api_key' => null]);

        $result = $this->generator->generateQuestions(['topic' => 'PHP'], 'anthropic');

        $this->assertEquals('Anthropic API key is missing.', $result);
    }

    public function test_generate_questions_returns_error_when_gemini_key_missing(): void
    {
        config(['services.gemini.api_key' => null]);

        $result = $this->generator->generateQuestions(['topic' => 'PHP'], 'gemini');

        $this->assertEquals('Gemini API key is missing.', $result);
    }

    public function test_generate_questions_returns_unsupported_for_unknown_provider(): void
    {
        $result = $this->generator->generateQuestions(['topic' => 'PHP'], 'unknown');

        $this->assertEquals('Unsupported provider selected.', $result);
    }

    public function test_generate_questions_accepts_string_prompt_for_legacy_support(): void
    {
        config(['services.openai.api_key' => null]);

        $result = $this->generator->generateQuestions('Generate 5 PHP questions', 'openai');

        $this->assertEquals('OpenAI API key is missing.', $result);
    }

    public function test_generate_questions_uses_provider_from_params_if_set(): void
    {
        config(['services.anthropic.api_key' => null]);

        $result = $this->generator->generateQuestions([
            'topic' => 'PHP',
            'provider' => 'anthropic',
        ], 'openai');

        $this->assertEquals('Anthropic API key is missing.', $result);
    }

    public function test_generate_questions_calls_openai_api_successfully(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                [
                                    'question_text' => 'What is PHP?',
                                    'type' => 'multiple_choice',
                                    'options' => [
                                        ['option' => 'A programming language', 'correct' => true],
                                        ['option' => 'A database', 'correct' => false],
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $result = $this->generator->generateQuestions(['topic' => 'PHP'], 'openai');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('What is PHP?', $result[0]['question_text']);
    }

    public function test_generate_questions_handles_openai_api_failure(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'error' => ['message' => 'Rate limit exceeded'],
            ], 429),
        ]);

        $result = $this->generator->generateQuestions(['topic' => 'PHP'], 'openai');

        $this->assertEquals('Rate limit exceeded', $result);
    }

    public function test_generate_questions_calls_anthropic_api_successfully(): void
    {
        config(['services.anthropic.api_key' => 'test-key']);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    [
                        'text' => json_encode([
                            ['question_text' => 'What is Laravel?'],
                        ]),
                    ],
                ],
            ]),
        ]);

        $result = $this->generator->generateQuestions(['topic' => 'Laravel'], 'anthropic');

        $this->assertIsArray($result);
        $this->assertEquals('What is Laravel?', $result[0]['question_text']);
    }

    public function test_generate_questions_calls_gemini_api_successfully(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode([['question_text' => 'What is Eloquent?']])],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $result = $this->generator->generateQuestions(['topic' => 'Eloquent'], 'gemini');

        $this->assertIsArray($result);
        $this->assertEquals('What is Eloquent?', $result[0]['question_text']);
    }

    public function test_generate_questions_handles_invalid_json_response(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'This is not JSON']],
                ],
            ]),
        ]);

        $result = $this->generator->generateQuestions(['topic' => 'PHP'], 'openai');

        $this->assertIsString($result);
        $this->assertStringContainsString('Failed to parse', $result);
    }

    public function test_generate_questions_handles_empty_response(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => '']],
                ],
            ]),
        ]);

        $result = $this->generator->generateQuestions(['topic' => 'PHP'], 'openai');

        $this->assertIsString($result);
        $this->assertStringContainsString('empty response', $result);
    }
}
