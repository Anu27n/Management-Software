<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
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
        $firstNames = ['Aarav', 'Vivaan', 'Aditya', 'Krishna', 'Anaya', 'Diya', 'Meera', 'Riya', 'Arjun', 'Ishaan'];
        $lastNames = ['Sharma', 'Verma', 'Gupta', 'Patel', 'Singh', 'Kumar', 'Jain', 'Mehta', 'Yadav', 'Kapoor'];
        $streets = ['MG Road', 'Park Street', 'Station Road', 'Main Bazaar', 'School Road', 'Temple Street'];
        $roles = ['teacher', 'parent', 'student', 'cashier'];

        $name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
        $uniqueToken = strtolower(Str::random(12));
        $email = 'user.' . $uniqueToken . '@example.test';

        return [
            'name' => $name,
            'email' => $email,
            'username' => 'user' . strtolower(Str::random(8)),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => $roles[array_rand($roles)],
            'phone' => (string) random_int(6000000000, 9999999999),
            'address' => random_int(10, 999) . ' ' . $streets[array_rand($streets)] . ', City',
            'profile_photo' => null,
            'is_active' => true,
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
        ]);
    }
}
