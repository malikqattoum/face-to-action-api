<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class LogService
{
    public function __construct(
        private OpenAIService $openAIService
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

        // 4. Create Log record
        $log = new Log([
            'user_id' => $userId,
            'customer_name' => $structuredData['customer_name'],
            'action_taken' => $structuredData['action_taken'],
            'amount' => $structuredData['amount'],
            'currency' => 'USD',
            'next_steps' => $structuredData['next_steps'],
            'recorded_at' => $recordedAt,
            'transcribed_text' => $transcribedText,
            'audio_path' => $audioPath,
        ]);
        $log->save();

        return $log;
    }
}
