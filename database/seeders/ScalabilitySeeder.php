<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Status;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScalabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating 50 Roles...');
        Role::factory()->count(50)->create();
        $roleIds = Role::pluck('id')->toArray();

        $this->command->info('Creating 10 Task Statuses...');
        Status::factory()->count(10)->create();
        $statusIds = Status::pluck('id')->toArray();

        $this->command->info('Creating 20 Tags...');
        Tag::factory()->count(20)->create();

        $this->command->info('Creating 1k Staff Users...');
        // Assume staff role has an ID. We'll just randomly assign from created roles or set a specific one
        $staffIds = collect();
        for ($i = 0; $i < 10; $i++) {
            $staff = User::factory()->count(100)->create([
                'role_id' => $roleIds[0] ?? 1,
                'is_approved' => 1,
            ]);
            $staffIds = $staffIds->merge($staff->pluck('id'));
        }

        $this->command->info('Creating 2k Clients...');
        $clientIds = collect();
        for ($i = 0; $i < 20; $i++) {
            $clients = User::factory()->count(100)->create([
                'role_id' => $roleIds[1] ?? 2,
                'is_approved' => 1,
            ]);
            $clientIds = $clientIds->merge($clients->pluck('id'));
        }

        $this->command->info('Creating 20k Tickets...');
        for ($i = 0; $i < 20; $i++) {
            $tickets = Ticket::factory()->count(1000)->make()->map(function ($ticket) use ($clientIds, $staffIds) {
                $ticket->user_id = $clientIds->random();
                $ticket->assigned_to = $staffIds->random();

                return $ticket->getAttributes();
            })->toArray();

            Ticket::insert($tickets);
            $this->command->info('Inserted '.(($i + 1) * 1000).' tickets.');
        }

        $this->command->info('Creating 10k Tasks...');
        for ($i = 0; $i < 10; $i++) {
            $tasks = Task::factory()->count(1000)->make()->map(function ($task) use ($clientIds, $staffIds, $statusIds) {
                $task->user_id = $clientIds->random();
                $task->assigned_to = $staffIds->random();
                $task->status_id = collect($statusIds)->random();

                return $task->getAttributes();
            })->toArray();

            Task::insert($tasks);
            $this->command->info('Inserted '.(($i + 1) * 1000).' tasks.');
        }

        $this->command->info('Scalability Seeding Complete!');
    }
}
