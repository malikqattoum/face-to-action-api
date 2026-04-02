<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class LogService
{
    public function __construct(
        private OpenAIService $openAIService,
        private AIActionExtractor $aiExtractor
    ) {}

    public function createFromAudio(UploadedFile $audioFile, \DateTimeInterface $recordedAt, int $userId): Log
    {
        // 1. Save audio file with UUID name
        $audioPath = $audioFile->storeAs(
            'logs',
            Str::uuid() . '.' . $audioFile->getClientOriginalExtension(),
            'local'
        );

        // 2. Transcribe via Whisper
        $transcribedText = $this->openAIService->transcribe($audioFile);

        // 3. Extract structured data via GPT
        $structuredData = $this->openAIService->extractStructuredData($transcribedText);

        // 4. Extract additional fields via heuristics
        $aiExtracted = $this->aiExtractor->extract($transcribedText);

        // 5. Merge extracted data (heuristics fill gaps)
        $actionTaken = $structuredData['action_taken'] ?? $aiExtracted['action_taken'];
        $nextSteps = $structuredData['next_steps'] ?? $aiExtracted['next_steps'];

        // 6. Create Log record
        $log = new Log([
            'user_id' => $userId,
            'customer_name' => $structuredData['customer_name'],
            'action_taken' => $actionTaken,
            'amount' => $structuredData['amount'],
            'currency' => 'USD',
            'next_steps' => $nextSteps,
            'recorded_at' => $recordedAt,
            'transcribed_text' => $transcribedText,
            'audio_path' => $audioPath,
            // AI Extraction fields
            'issue_type' => $aiExtracted['issue_type'],
            'parts_used' => $aiExtracted['parts_used'],
            'estimated_price' => $aiExtracted['estimated_price'],
            'service_type' => $aiExtracted['service_type'],
        ]);
        $log->save();

        return $log;
    }
}
