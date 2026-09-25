<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement([
                'Solar Farm Alpha', 'Wind Turbine Array', 'Grid Storage Unit',
                'Hydro Plant Delta', 'EV Charging Hub',
            ]),
            'description' => fake()->sentence(),
            'status' => fake()->randomElement(ProjectStatus::cases()),
        ];
    }
}
