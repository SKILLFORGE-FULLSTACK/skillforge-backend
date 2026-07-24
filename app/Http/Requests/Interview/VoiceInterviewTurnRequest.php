<?php

namespace App\Http\Requests\Interview;

use Illuminate\Foundation\Http\FormRequest;

class VoiceInterviewTurnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'audio' => 'required|file|mimetypes:audio/webm,audio/wav,audio/x-wav,audio/mpeg,audio/mp4,audio/ogg,video/webm|max:20480',
        ];
    }

    public function messages(): array
    {
        return [
            'audio.required' => "L'enregistrement audio est requis.",
            'audio.mimetypes' => 'Format audio non supporté.',
            'audio.max' => "L'enregistrement est trop volumineux (max 20 Mo).",
        ];
    }
}
