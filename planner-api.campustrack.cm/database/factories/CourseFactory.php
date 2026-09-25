<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'code' => strtoupper(fake()->unique()->lexify('MAT???')),
            'description' => fake()->paragraph(),
            'department_id' => Department::factory(),
            'coefficient' => fake()->randomFloat(1, 1, 5),
            'hours_per_week' => fake()->numberBetween(1, 6),
            'is_active' => true,
        ];
    }
}
