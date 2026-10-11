<?php

namespace App\Livewire;

use App\Models\TaskLog;
use Livewire\Component;

class TaskActivityTab extends Component
{
    public $taskId;

    public $limit = 15;

    public function loadMore()
    {
        $this->limit += 15;
    }

    public function render()
    {
        $logs = collect();
        $hasMore = false;

        if ($this->taskId) {
            $logs = TaskLog::with('user')
                ->where('task_id', $this->taskId)
                ->orderBy('created_at', 'desc')
                ->take($this->limit)
                ->get();

            $totalCount = TaskLog::where('task_id', $this->taskId)->count();
            $hasMore = $totalCount > $this->limit;
        }

        return view('livewire.task-activity-tab', [
            'logs' => $logs,
            'hasMore' => $hasMore,
        ]);
    }
}
