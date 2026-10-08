<?php

namespace Database\Factories;

use App\Models\Block;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlockFactory extends Factory
{
    protected $model = Block::class;

    public function definition(): array
    {
        return [
            'code' => 'BLOK-' . strtoupper(fake()->unique()->bothify('##-??')),
            'name' => fake()->words(2, true),
            'polygon' => null,
            'hectare' => fake()->randomFloat(2, 1, 500),
            'pic_user_id' => null,
            'is_active' => true,
        ];
    }
}
