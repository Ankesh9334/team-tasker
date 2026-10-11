<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\TaskLog;
use Illuminate\Support\Facades\Auth;

class ProjectObserver
{
    /**
     * Handle the Project "created" event.
     */
    public function created(Project $project): void
    {
        $userId = Auth::check() ? Auth::id() : 1; // Fallback to 1 if seeded/system

        TaskLog::create([
            'project_id' => $project->id,
            'user_id' => $userId,
            'note' => 'Created project <span class="fw-bold">'.$project->name.'</span>',
            'type' => 'log',
        ]);
    }

    /**
     * Handle the Project "updated" event.
     */
    public function updated(Project $project): void
    {
        $userId = Auth::check() ? Auth::id() : 1;
        $changes = $project->getDirty();
        $original = $project->getOriginal();

        $note = 'Updated project details.';

        if (isset($changes['status']) && isset($original['status'])) {
            $note = 'Changed project status from <span class="badge bg-secondary">'.$original['status'].'</span> to <span class="badge bg-primary">'.$changes['status'].'</span>';
        }

        TaskLog::create([
            'project_id' => $project->id,
            'user_id' => $userId,
            'note' => $note,
            'type' => 'log',
        ]);
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        // Usually, we don't log deleted if cascade delete removes logs.
        // But if soft deletes, we could log it.
    }
}
