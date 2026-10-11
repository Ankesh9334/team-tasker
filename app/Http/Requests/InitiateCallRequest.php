<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'receiver_id' => 'required|exists:users,id|different:'.auth()->id(),
            'mode' => 'required|string|in:audio,video',
        ];
    }
}
