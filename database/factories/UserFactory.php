<?php

namespace Database\Factories;

use App\Modules\User\Model\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->regexify('^0[1-9][0-9]{8,13}$'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'is_verified' => false,
            'role' => 2,
            'google_id' => null,
            'apple_id' => null,
            // Model cast `password => hashed`, so provide plaintext here.
            'password' => static::$password ??= 'password',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'is_verified' => false,
        ]);
    }
}
