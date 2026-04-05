<?php

namespace Database\Factories;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Institution;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'institution_id' => Institution::factory(),
            'source' => OrderSource::Client,
            'status' => OrderStatus::New,
            'total_bonus' => fake()->numberBetween(100, 10000),
            'total_weight_grams' => fake()->numberBetween(100, 5000),
            'reserved_bonus_amount' => 0,
            'placed_at' => now(),
            'status_changed_at' => now(),
        ];
    }
}
