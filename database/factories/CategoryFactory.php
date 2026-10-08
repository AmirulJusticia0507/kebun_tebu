<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'icon_marker' => 'fire',
            'color_code' => '#'.strtoupper(dechex(rand(0x100000, 0xFFFFFF))),
            'sla_hours' => 24,
            'checklist_template' => null,
        ];
    }
}
