<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\Material;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class MaterialsFactory extends Factory
{
    private static array $materials = [
        'Картон',
        'Поліетилен',
        'Алюмінієва фольга',
    ];
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'description' => 'empty description',
            'url' => null,
            'quantity' => rand(0, 1000),
        ];
    }
}
