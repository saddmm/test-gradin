<?php

namespace Database\Factories;

use App\Models\Courier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Courier>
 */
class CourierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Courier>
     */
    protected $model = Courier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'CR-'.fake()->unique()->numerify('####'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('+6281#########'),
            'vehicle_type' => fake()->randomElement(['motorcycle', 'car', 'van', 'truck']),
            'license_plate' => fake()->bothify('B #### ???'),
            'level' => fake()->numberBetween(1, 5),
            'is_active' => fake()->boolean(85),
            'registered_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
