<?php

namespace Database\Factories;

use App\Enums\RecipientType;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'bin' => fake()->unique()->numerify('############'),
            'password' => static::$password ??= Hash::make('password'),
            'institution_id' => null,
            'recipient_type' => RecipientType::Client,
            'recipient_first_name' => null,
            'recipient_last_name' => null,
            'recipient_bin' => null,
            'bonus_balance' => fake()->numberBetween(0, 10000),
            'bonus_reserved' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
