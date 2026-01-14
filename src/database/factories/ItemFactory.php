<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'seller_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'brand' => $this->faker->optional()->company(),
            'description' => $this->faker->realText(120),
            'price' => $this->faker->numberBetween(300, 300000),
            'image_path' => 'items/dummy.jpg',
            'status' => 'on_sale',
            'condition' => $this->faker->numberBetween(1, 4),
            'processing_expires_at' => null,
        ];
    }

    public function sold(): self
    {
        return $this->state(fn() => ['status' => 'sold']);
    }

    public function processing(?Carbon $expiresAt = null): self
    {
        return $this->state(fn() => [
            'status' => 'processing',
            'processing_expires_at' => $expiresAt ?? now()->addMinutes(10),
        ]);
    }
}
