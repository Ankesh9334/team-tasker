<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskLog;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskScalabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get 10 tasks or create them
        $tasks = Task::inRandomOrder()->limit(10)->get();
        if ($tasks->count() < 10) {
            $tasks = $tasks->merge(Task::factory()->count(10 - $tasks->count())->create());
        }

        $users = User::limit(50)->pluck('id')->toArray();
        if (empty($users)) {
            $users = [User::factory()->create()->id];
        }

        foreach ($tasks as $task) {
            $this->command->info("Seeding 1000 Activities and 1000 TimeLogs for Task ID: {$task->id} ({$task->title})");

            // Prepare Task Logs (Activities)
            $activities = [];
            for ($i = 0; $i < 1000; $i++) {
                $activities[] = [
                    'task_id' => $task->id,
                    'user_id' => $users[array_rand($users)],
                    'note' => 'Scalability test activity log entry #'.$i,
                    'type' => rand(0, 1) ? 'log' : 'message',
                    'created_at' => now()->subMinutes(rand(1, 10000)),
                    'updated_at' => now(),
                ];
            }

            // Insert in chunks of 500
            foreach (array_chunk($activities, 500) as $chunk) {
                TaskLog::insert($chunk);
            }

            $this->command->info('1000 Activities seeded.');

            // Prepare Time Logs
            $timeLogs = [];
            for ($i = 0; $i < 1000; $i++) {
                $start = now()->subMinutes(rand(100, 10000));
                $end = (clone $start)->addMinutes(rand(30, 240));
                $duration = $end->diffInSeconds($start);

                $timeLogs[] = [
                    'task_id' => $task->id,
                    'user_id' => $users[array_rand($users)],
                    'start_time' => $start,
                    'end_time' => $end,
                    'duration' => $duration,
                    'description' => 'Scalability test time log entry #'.$i,
                    'mode' => rand(0, 1) ? 'Inside Office' : 'Remote',
                    'bucket' => 'Development',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach (array_chunk($timeLogs, 500) as $chunk) {
                TimeLog::insert($chunk);
            }

            $this->command->info("1000 TimeLogs seeded for Task {$task->id}.");
        }
    }
}
