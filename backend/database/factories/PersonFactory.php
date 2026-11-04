<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;
use Symfony\Component\Intl\Countries;

/** @extends Factory<Person> */
class PersonFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $joined = fake()->boolean(80) ? fake()->dateTimeBetween('-8 years', '-1 year') : null;

        return [
            'display_name' => fake()->unique()->userName(),
            'country_code' => fake()->optional(0.9)->randomElement(array_keys(Countries::getNames())),
            'website' => fake()->optional(0.6)->passthrough('https://example.com/'.fake()->unique()->slug(2)),
            'bio' => fake()->optional(0.85)->paragraph(4),
            'joined_on' => $joined,
            'left_on' => $joined && fake()->boolean(25) ? fake()->dateTimeBetween($joined) : null,
        ];
    }
}
