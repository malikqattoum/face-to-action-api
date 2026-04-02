<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    public function transcribe(UploadedFile $audioFile): string
    {
        $response = Http::timeout(60)
            ->attach(
                'file',
                $audioFile->getContent(),
                $audioFile->getClientOriginalName()
            )
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => 'whisper-1',
                'response_format' => 'text',
            ]);

        if ($response->failed()) {
            Log::error('Whisper API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('Failed to transcribe audio: ' . $response->body());
        }

        return $response->json('text');
    }

    public function extractStructuredData(string $transcribedText): array
    {
        $prompt = "Extract from this service log. Return ONLY valid JSON with these fields: customer_name (string or null), action_taken (string or null), amount (number or null), next_steps (string or null). Example: {\"customer_name\": \"Mr. Smith\", \"action_taken\": \"Fixed leak\", \"amount\": 150, \"next_steps\": \"Return Tuesday for valve\"}";

        $response = Http::timeout(30)
            ->withHeader('Authorization', 'Bearer ' . $this->apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $prompt],
                    ['role' => 'user', 'content' => $transcribedText],
                ],
                'temperature' => 0,
            ]);

        if ($response->failed()) {
            Log::error('GPT API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('Failed to extract structured data: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');

        // Try to extract JSON from the response
        $content = trim($content);
        if (str_starts_with($content, '```json')) {
            $content = substr($content, 7);
        }
        if (str_starts_with($content, '```')) {
            $content = substr($content, 3);
        }
        if (str_ends_with(trim($content), '```')) {
            $content = substr(trim($content), 0, -3);
        }

        $data = json_decode(trim($content), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse GPT JSON response', ['content' => $content]);
            return [
                'customer_name' => null,
                'action_taken' => null,
                'amount' => null,
                'next_steps' => null,
            ];
        }

        return [
            'customer_name' => $data['customer_name'] ?? null,
            'action_taken' => $data['action_taken'] ?? null,
            'amount' => isset($data['amount']) ? (is_numeric($data['amount']) ? (float) $data['amount'] : null) : null,
            'next_steps' => $data['next_steps'] ?? null,
        ];
    }
}
