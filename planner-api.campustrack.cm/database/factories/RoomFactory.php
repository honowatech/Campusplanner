<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['classroom', 'lab', 'amphitheater', 'conference', 'study_room'];

        return [
            'name' => fake()->randomElement(['Salle', 'Room', 'Amphi', 'Lab']).' '.fake()->numberBetween(1, 500),
            'code' => fake()->unique()->regexify('[A-Z]{2}[0-9]{3}'),
            'department_id' => Department::factory(),
            'type' => fake()->randomElement($types),
            'capacity' => fake()->numberBetween(10, 200),
            'floor' => fake()->numberBetween(0, 5),
            'building' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'has_projector' => fake()->boolean(70),
            'has_computers' => fake()->boolean(30),
            'has_whiteboard' => fake()->boolean(80),
            'is_active' => true,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
