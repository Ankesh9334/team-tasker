<?php

namespace App\Livewire;

use App\Models\TimeLog;
use Livewire\Component;

class TaskTimeLogTab extends Component
{
    public $taskId;

    public $limit = 15;

    public function loadMore()
    {
        $this->limit += 15;
    }

    public function render()
    {
        $timeLogs = collect();
        $hasMore = false;

        if ($this->taskId) {
            $timeLogs = TimeLog::with('user')
                ->where('task_id', $this->taskId)
                ->orderBy('created_at', 'desc')
                ->take($this->limit)
                ->get();

            $totalCount = TimeLog::where('task_id', $this->taskId)->count();
            $hasMore = $totalCount > $this->limit;
        }

        return view('livewire.task-time-log-tab', [
            'timeLogs' => $timeLogs,
            'hasMore' => $hasMore,
        ]);
    }
}
