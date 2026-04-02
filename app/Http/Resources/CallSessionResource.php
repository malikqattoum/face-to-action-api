<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CallSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contact_name' => $this->contact_name,
            'phone_number' => $this->phone_number,
            'direction' => $this->direction,
            'duration_seconds' => $this->duration_seconds,
            'duration_formatted' => $this->duration_seconds > 0
                ? floor($this->duration_seconds / 60) . 'm ' . ($this->duration_seconds % 60) . 's'
                : null,
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'notes' => $this->notes,
            'has_voice_memo' => $this->has_voice_memo,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
