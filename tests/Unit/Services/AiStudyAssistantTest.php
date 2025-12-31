<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Services\AiStudyAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiStudyAssistantTest extends TestCase
{
    use RefreshDatabase;

    private AiStudyAssistant $assistant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assistant = new AiStudyAssistant;
    }

    public function test_providers_constant_contains_expected_values(): void
    {
        $this->assertContains('openai', AiStudyAssistant::PROVIDERS);
        $this->assertContains('anthropic', AiStudyAssistant::PROVIDERS);
        $this->assertContains('gemini', AiStudyAssistant::PROVIDERS);
    }

    public function test_ask_question_returns_error_when_openai_key_missing(): void
    {
        config(['services.openai.api_key' => null]);

        $result = $this->assistant->askQuestion('What is PHP?', null, 'openai');

        $this->assertEquals('OpenAI API key is missing.', $result);
    }

    public function test_ask_question_returns_error_when_anthropic_key_missing(): void
    {
        config(['services.anthropic.api_key' => null]);

        $result = $this->assistant->askQuestion('What is PHP?', null, 'anthropic');

        $this->assertEquals('Anthropic API key is missing.', $result);
    }

    public function test_ask_question_returns_error_when_gemini_key_missing(): void
    {
        config(['services.gemini.api_key' => null]);

        $result = $this->assistant->askQuestion('What is PHP?', null, 'gemini');

        $this->assertEquals('Gemini API key is missing.', $result);
    }

    public function test_ask_question_returns_unsupported_for_unknown_provider(): void
    {
        $result = $this->assistant->askQuestion('What is PHP?', null, 'unknown');

        $this->assertEquals('Unsupported provider selected.', $result);
    }

    public function test_ask_question_calls_openai_successfully(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'PHP is a server-side scripting language.']],
                ],
            ]),
        ]);

        $result = $this->assistant->askQuestion('What is PHP?', null, 'openai');

        $this->assertEquals('PHP is a server-side scripting language.', $result);
    }

    public function test_ask_question_includes_course_context(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        $course = Course::factory()->create([
            'title' => 'Laravel Masterclass',
            'description' => 'Learn Laravel from scratch',
        ]);

        Http::fake([
            'api.openai.com/*' => function ($request) {
                $content = $request->data()['messages'][1]['content'];

                $this->assertStringContainsString('Laravel Masterclass', $content);
                $this->assertStringContainsString('COURSE CONTEXT', $content);

                return Http::response([
                    'choices' => [
                        ['message' => ['content' => 'Great question!']],
                    ],
                ]);
            },
        ]);

        $this->assistant->askQuestion('How do I create a controller?', $course, 'openai');
    }

    public function test_ask_question_includes_lesson_content(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        $course = Course::factory()->create(['title' => 'PHP Basics']);
        Lesson::factory()->create([
            'course_id' => $course->id,
            'title' => 'Introduction to Variables',
            'lesson_text' => 'Variables in PHP start with a dollar sign.',
            'is_published' => true,
        ]);

        Http::fake([
            'api.openai.com/*' => function ($request) {
                $content = $request->data()['messages'][1]['content'];

                $this->assertStringContainsString('Introduction to Variables', $content);
                $this->assertStringContainsString('Relevant Course Content', $content);

                return Http::response([
                    'choices' => [
                        ['message' => ['content' => 'Answer']],
                    ],
                ]);
            },
        ]);

        $this->assistant->askQuestion('What are variables?', $course, 'openai');
    }

    public function test_ask_question_includes_quiz_topics(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        $course = Course::factory()->create(['title' => 'PHP Course']);
        Quiz::factory()->create([
            'course_id' => $course->id,
            'title' => 'PHP Fundamentals Quiz',
            'is_published' => true,
        ]);

        Http::fake([
            'api.openai.com/*' => function ($request) {
                $content = $request->data()['messages'][1]['content'];

                $this->assertStringContainsString('PHP Fundamentals Quiz', $content);
                $this->assertStringContainsString('Quiz Topics', $content);

                return Http::response([
                    'choices' => [
                        ['message' => ['content' => 'Answer']],
                    ],
                ]);
            },
        ]);

        $this->assistant->askQuestion('What topics are covered?', $course, 'openai');
    }

    public function test_ask_question_handles_api_failure(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'error' => ['message' => 'Rate limit exceeded'],
            ], 429),
        ]);

        $result = $this->assistant->askQuestion('What is PHP?', null, 'openai');

        $this->assertEquals('Rate limit exceeded', $result);
    }

    public function test_ask_question_with_anthropic(): void
    {
        config(['services.anthropic.api_key' => 'test-key']);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['text' => 'Anthropic response about PHP'],
                ],
            ]),
        ]);

        $result = $this->assistant->askQuestion('What is PHP?', null, 'anthropic');

        $this->assertEquals('Anthropic response about PHP', $result);
    }

    public function test_ask_question_with_gemini(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Gemini response about PHP'],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $result = $this->assistant->askQuestion('What is PHP?', null, 'gemini');

        $this->assertEquals('Gemini response about PHP', $result);
    }

    public function test_ask_question_handles_empty_response(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => null]],
                ],
            ]),
        ]);

        $result = $this->assistant->askQuestion('What is PHP?', null, 'openai');

        $this->assertEquals('No response received.', $result);
    }

    public function test_ask_question_without_course_context(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => function ($request) {
                $content = $request->data()['messages'][1]['content'];

                $this->assertStringNotContainsString('COURSE CONTEXT', $content);
                $this->assertStringContainsString('STUDENT QUESTION', $content);

                return Http::response([
                    'choices' => [
                        ['message' => ['content' => 'Answer']],
                    ],
                ]);
            },
        ]);

        $this->assistant->askQuestion('General question', null, 'openai');
    }

    public function test_openai_uses_system_message(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => function ($request) {
                $messages = $request->data()['messages'];

                $this->assertEquals('system', $messages[0]['role']);
                $this->assertStringContainsString('study assistant', $messages[0]['content']);

                return Http::response([
                    'choices' => [
                        ['message' => ['content' => 'Answer']],
                    ],
                ]);
            },
        ]);

        $this->assistant->askQuestion('Test', null, 'openai');
    }
}
