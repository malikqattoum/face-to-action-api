<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'customer_name' => $this->customer_name,
            'action_taken' => $this->action_taken,
            'amount' => $this->amount ? (float) $this->amount : null,
            'currency' => $this->currency,
            'next_steps' => $this->next_steps,
            'recorded_at' => $this->recorded_at?->toIso8601String(),
            'transcribed_text' => $this->transcribed_text,
            'audio_url' => $this->audio_path ? url('storage/' . $this->audio_path) : null,
            // AI Extraction fields
            'issue_type' => $this->issue_type,
            'parts_used' => $this->parts_used ?? [],
            'estimated_price' => $this->estimated_price ? (float) $this->estimated_price : null,
            'service_type' => $this->service_type,
            // Photos
            'photos' => $this->whenLoaded('photos', function () {
                return $this->photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'caption' => $photo->caption,
                        'url' => $photo->url,
                        'created_at' => $photo->created_at->toIso8601String(),
                    ];
                });
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
