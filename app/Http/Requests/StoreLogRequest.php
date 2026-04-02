<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'audio' => ['required', 'file', 'mimes:webm,m4a,audio/webm,audio/mp4', 'max:10240'],
            'recorded_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'audio.required' => 'An audio file is required.',
            'audio.file' => 'The uploaded file must be a valid file.',
            'audio.mimes' => 'Audio must be a webm or m4a file.',
            'audio.max' => 'Audio file must not exceed 10MB.',
        ];
    }
}
