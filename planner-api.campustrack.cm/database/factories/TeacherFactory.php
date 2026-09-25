<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null, // Can be linked to a user if needed
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'speciality' => fake()->jobTitle(),
            'department_id' => Department::factory(),
            'max_hours_per_week' => fake()->numberBetween(10, 40),
            'is_active' => true,
            'hired_at' => fake()->dateTimeBetween('-10 years', '-1 year'),
        ];
    }
}
