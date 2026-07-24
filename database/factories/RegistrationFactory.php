<?php

namespace Database\Factories;

use App\Domain\Registration\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    public function definition(): array
    {
        $issued = fake()->dateTimeBetween('-3 years', '-1 month');

        return [
            'nie_number' => 'AKD ' . fake()->unique()->numerify('###########'),
            'issuer' => 'BPOM',
            'issued_at' => $issued,
            'expired_at' => (clone $issued)->modify('+5 years'),
            'notes' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state([
            'issued_at' => now()->subYears(6),
            'expired_at' => now()->subMonth(),
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state([
            'issued_at' => now()->subYears(4)->subMonths(9),
            'expired_at' => now()->addMonths(2),
        ]);
    }
}
