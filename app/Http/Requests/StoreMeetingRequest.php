<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:direct_call,group_call,instant_meeting,project_meeting,task_meeting,scheduled_meeting',
            'mode' => 'required|string|in:audio,video',
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'scheduled_at' => 'nullable|date',
            'duration' => 'nullable|integer|min:1|max:1440',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'exists:users,id',
        ];
    }
}
