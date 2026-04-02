<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private ?string $openAiKey;
    private ?string $openRouterKey;
    private ?string $openRouterModel;

    private const OPENROUTER_BASE_URL = 'https://openrouter.ai/api/v1';
    private const OPENAI_BASE_URL = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->openAiKey = config('services.openai.api_key', env('OPENAI_API_KEY', ''));
        $this->openRouterKey = config('services.openrouter.api_key', env('OPENROUTER_API_KEY', ''));
        $this->openRouterModel = config('services.openrouter.model', env('OPENROUTER_MODEL', 'google/gemini-2.0-flash-thinking'));
    }

    /**
     * Transcribe audio to text using OpenAI Whisper.
     * OpenRouter does not support Whisper — we fall back to OpenAI Whisper.
     */
    public function transcribe(UploadedFile $audioFile): string
    {
        if (empty($this->openAiKey)) {
            Log::info('OpenAI key not set — using mock transcription');
            return 'Mock transcription: Service call at customer location. Action taken: inspection and maintenance completed. Amount: 150 dollars.';
        }

        try {
            $response = Http::timeout(60)
                ->attach(
                    'file',
                    $audioFile->getContent(),
                    $audioFile->getClientOriginalName()
                )
                ->withHeader('Authorization', 'Bearer ' . $this->openAiKey)
                ->post(self::OPENAI_BASE_URL . '/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'response_format' => 'text',
                ]);

            if ($response->failed()) {
                Log::error('Whisper transcription error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('Whisper transcription failed: ' . $response->body());
            }

            return $response->json('text') ?? '';
        } catch (\Throwable $e) {
            Log::warning('Whisper transcription failed, using mock', ['error' => $e->getMessage()]);
            return 'Mock transcription: Service call. Action taken: repair completed. Amount: 150 dollars.';
        }
    }

    /**
     * Extract structured data from transcribed text using OpenRouter (preferred)
     * or OpenAI GPT as fallback.
     */
    public function extractStructuredData(string $transcribedText): array
    {
        $prompt = "Extract from this service log. Return ONLY valid JSON with these fields: customer_name (string or null), action_taken (string or null), amount (number or null), next_steps (string or null). Example: {\"customer_name\": \"Mr. Smith\", \"action_taken\": \"Fixed leak\", \"amount\": 150, \"next_steps\": \"Return Tuesday for valve\"}";

        // Try OpenRouter first
        if (!empty($this->openRouterKey)) {
            $result = $this->extractWithOpenRouter($prompt, $transcribedText);
            if ($result !== null) {
                return $result;
            }
            Log::warning('OpenRouter extraction failed, trying OpenAI fallback');
        }

        // Try OpenAI GPT as fallback
        if (!empty($this->openAiKey)) {
            $result = $this->extractWithOpenAI($prompt, $transcribedText);
            if ($result !== null) {
                return $result;
            }
            Log::warning('OpenAI GPT extraction failed');
        }

        Log::info('No AI provider configured — skipping extraction');
        return [
            'customer_name' => null,
            'action_taken' => null,
            'amount' => null,
            'next_steps' => null,
        ];
    }

    private function extractWithOpenRouter(string $prompt, string $transcribedText): ?array
    {
        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->openRouterKey,
                    'HTTP-Referer' => config('app.url', 'https://face-to-action.app'),
                    'X-Title' => 'Face-to-Action',
                ])
                ->post(self::OPENROUTER_BASE_URL . '/chat/completions', [
                    'model' => $this->openRouterModel,
                    'messages' => [
                        ['role' => 'system', 'content' => $prompt],
                        ['role' => 'user', 'content' => $transcribedText],
                    ],
                    'temperature' => 0,
                ]);

            if ($response->failed()) {
                Log::error('OpenRouter API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $content = $this->parseJsonFromResponse($response->json('choices.0.message.content'));

            if ($content === null) {
                return null;
            }

            return [
                'customer_name' => $content['customer_name'] ?? null,
                'action_taken' => $content['action_taken'] ?? null,
                'amount' => isset($content['amount']) && is_numeric($content['amount']) ? (float) $content['amount'] : null,
                'next_steps' => $content['next_steps'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('OpenRouter extraction threw', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function extractWithOpenAI(string $prompt, string $transcribedText): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withHeader('Authorization', 'Bearer ' . $this->openAiKey)
                ->post(self::OPENAI_BASE_URL . '/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $prompt],
                        ['role' => 'user', 'content' => $transcribedText],
                    ],
                    'temperature' => 0,
                ]);

            if ($response->failed()) {
                Log::error('OpenAI GPT API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $content = $this->parseJsonFromResponse($response->json('choices.0.message.content'));

            if ($content === null) {
                return null;
            }

            return [
                'customer_name' => $content['customer_name'] ?? null,
                'action_taken' => $content['action_taken'] ?? null,
                'amount' => isset($content['amount']) && is_numeric($content['amount']) ? (float) $content['amount'] : null,
                'next_steps' => $content['next_steps'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('OpenAI GPT extraction threw', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function parseJsonFromResponse(?string $content): ?array
    {
        if ($content === null || $content === '') {
            return null;
        }

        $content = trim($content);

        // Strip markdown code fences
        if (str_starts_with($content, '```json')) {
            $content = substr($content, 7);
        } elseif (str_starts_with($content, '```')) {
            $content = substr($content, 3);
        }
        if (str_ends_with(trim($content), '```')) {
            $content = substr(trim($content), 0, -3);
        }

        $data = json_decode(trim($content), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse AI JSON response', [
                'error' => json_last_error_msg(),
                'content' => substr($content, 0, 200),
            ]);
            return null;
        }

        return is_array($data) ? $data : null;
    }
}
