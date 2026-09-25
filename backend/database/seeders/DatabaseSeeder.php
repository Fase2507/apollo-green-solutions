<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Demo User',
            'email' => 'demo@apollo.test',
            'password' => Hash::make('password'),
        ]);

        $projects = Project::factory()->count(3)->create(['user_id' => $user->id]);

        foreach ($projects as $project) {
            Task::factory()->count(5)->create(['project_id' => $project->id]);
        }
    }
}
