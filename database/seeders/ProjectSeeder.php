<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::pluck('id')->toArray();

        if (empty($users)) {
            $users = [\App\Models\User::factory()->create()->id];
        }

        $projects = [
            [
                'name' => 'Website Redesign',
                'description' => 'Complete overhaul of the corporate website.',
                'status' => 'In Progress',
                'start_date' => now()->subDays(10),
                'deadline' => now()->addDays(30),
            ],
            [
                'name' => 'Mobile App Development',
                'description' => 'Build iOS and Android applications.',
                'status' => 'Planning',
                'start_date' => now()->addDays(5),
                'deadline' => now()->addDays(90),
            ],
            [
                'name' => 'Marketing Campaign Q3',
                'description' => 'Execution of the Q3 digital marketing strategy.',
                'status' => 'Completed',
                'start_date' => now()->subDays(60),
                'deadline' => now()->subDays(5),
            ],
        ];

        foreach ($projects as $projectData) {
            $projectData['user_id'] = $users[array_rand($users)];
            \App\Models\Project::create($projectData);
        }
    }
}
