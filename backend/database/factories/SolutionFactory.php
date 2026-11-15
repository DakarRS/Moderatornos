<?php

namespace Database\Factories;

use App\Enums\Edition;
use App\Models\Solution;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Solution> */
class SolutionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(4),
            'symptoms' => null,
            'cause' => null,
            'fix' => fake()->paragraph(),
            'edition' => Edition::Both,
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false]);
    }

    /** @param  list<string>  $patterns */
    public function withSignatures(array $patterns): static
    {
        return $this->afterCreating(function (Solution $solution) use ($patterns) {
            $solution->signatures()->createMany(array_map(fn (string $pattern) => ['pattern' => $pattern], $patterns));
        });
    }
}
