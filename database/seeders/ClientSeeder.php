<?php

namespace Database\Seeders;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $batchSize = 1000;
        $totalRecords = 10000;
        $password = Hash::make('password');
        $now = Carbon::now();

        for ($i = 0; $i < $totalRecords / $batchSize; $i++) {
            $clients = [];
            for ($j = 0; $j < $batchSize; $j++) {
                $clients[] = [
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                    'password' => $password,
                    'phone' => fake()->phoneNumber(),
                    'company' => fake()->company(),
                    'is_approved' => true,
                    'status' => 'active',
                    'email_verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            Client::insert($clients);
        }
    }
}
