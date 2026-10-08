<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        $category = Category::factory()->create();
        $reportedAt = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'user_id' => User::factory(),
            'client_uuid' => fake()->uuid(),
            'category_id' => $category->id,
            'block_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'latitude' => fake()->latitude(-90, 90),
            'longitude' => fake()->longitude(-180, 180),
            'block_code' => null,
            'photo_url' => null,
            'status' => 'OPEN',
            'admin_note' => null,
            'handled_by' => null,
            'reported_at' => $reportedAt,
            'resolved_at' => null,
            'voice_note_url' => null,
            'checklist_answers' => null,
            'sla_deadline' => $category->sla_hours
                ? \Illuminate\Support\Carbon::parse($reportedAt)->addHours($category->sla_hours)
                : null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'CLOSED',
            'resolved_at' => now(),
        ]);
    }

    public function onProgress(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'ON_PROGRESS']);
    }
}
