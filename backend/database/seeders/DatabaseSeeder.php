<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Runs on every container start, so it must not duplicate or overwrite edited data.
    public function run(): void
    {
        // Fake people and a known admin password must never reach a real site.
        if (app()->isProduction()) {
            return;
        }

        User::firstOrCreate(
            ['username' => 'admin'],
            ['password' => 'password', 'role' => UserRole::Admin],
        );

        // Only when empty, so a renamed or removed category doesn't come back on restart.
        if (Category::doesntExist()) {
            Category::create(['name' => 'Moderator', 'slug' => 'moderator']);
            Category::create(['name' => 'Former Moderator', 'slug' => 'former-moderator']);
        }

        if (Person::doesntExist()) {
            $categories = Category::all();
            Person::factory()->count(30)->create()->each(fn (Person $person) => $person->categories()->attach($categories->random()));
        }
    }
}
