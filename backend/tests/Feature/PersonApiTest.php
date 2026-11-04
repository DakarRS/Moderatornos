<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_oldest_joined_first_then_undated_people_with_ties_by_name(): void
    {
        foreach ([
            ['Undated B', null],
            ['Recent', '2024-01-01'],
            ['Undated a', null],
            ['Tied Z', '2020-05-05'],
            ['Oldest', '2015-01-01'],
            ['tied a', '2020-05-05'],
        ] as [$name, $joined]) {
            Person::factory()->create(['display_name' => $name, 'joined_on' => $joined]);
        }

        $names = collect($this->getJson('/api/people')->assertOk()->json('data'))->pluck('display_name')->all();

        $this->assertSame(['Oldest', 'tied a', 'Tied Z', 'Recent', 'Undated a', 'Undated B'], $names);
    }

    public function test_undated_people_sort_by_name_and_paginate(): void
    {
        $category = Category::factory()->create();
        Person::factory()->hasAttached($category)->create(['display_name' => 'Zed Young', 'country_code' => 'DE', 'joined_on' => null]);
        Person::factory()->hasAttached($category)->create(['display_name' => 'Ada Adams', 'country_code' => 'FR', 'joined_on' => null]);

        $this->getJson('/api/people?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.display_name', 'Ada Adams')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonStructure(['data' => [[
                'id', 'display_name',
                'categories' => [['name', 'slug']], 'country' => ['code', 'name'], 'website', 'bio', 'avatar_url', 'updated_at',
            ]]]);
    }

    public function test_sorting_ignores_case(): void
    {
        foreach (['Zed', 'adam', 'Bob'] as $name) {
            Person::factory()->create(['display_name' => $name, 'joined_on' => null]);
        }

        $this->getJson('/api/people')
            ->assertJsonPath('data.0.display_name', 'adam')
            ->assertJsonPath('data.1.display_name', 'Bob')
            ->assertJsonPath('data.2.display_name', 'Zed');
    }

    public function test_search_ignores_case_and_old_name_fields_are_gone(): void
    {
        $person = Person::factory()->create(['display_name' => 'xXModXx']);

        $this->getJson('/api/people?search=xxmod')->assertJsonCount(1, 'data');
        foreach (['first_name', 'last_name', 'full_name'] as $field) {
            $this->getJson("/api/people/{$person->id}")->assertJsonMissingPath("data.{$field}");
        }
    }

    public function test_search_matches_each_word_of_a_display_name(): void
    {
        Person::factory()->create(['display_name' => 'Ada Lovelace']);
        Person::factory()->create(['display_name' => 'Ada Yonath']);

        $this->getJson('/api/people?search=ada%20love')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.display_name', 'Ada Lovelace');
    }

    public function test_search_matches_a_country_name_and_combines_with_a_name(): void
    {
        Person::factory()->create(['display_name' => 'Ada', 'country_code' => 'DE']);
        Person::factory()->create(['display_name' => 'Ben', 'country_code' => 'DE']);
        Person::factory()->create(['display_name' => 'Ada', 'country_code' => 'FR']);

        $this->getJson('/api/people?search=germany')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/people?search=ada%20germany')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.country', ['code' => 'DE', 'name' => 'Germany']);
    }

    public function test_country_website_and_bio_are_optional(): void
    {
        $person = Person::factory()->create(['country_code' => null, 'website' => null, 'bio' => null]);

        $this->getJson("/api/people/{$person->id}")
            ->assertOk()
            ->assertJsonPath('data.country', null)
            ->assertJsonPath('data.website', null)
            ->assertJsonPath('data.bio', null);
    }

    public function test_joined_and_left_dates_are_plain_dates_and_optional(): void
    {
        $dated = Person::factory()->create(['joined_on' => '2021-03-12', 'left_on' => '2024-06-03']);
        $undated = Person::factory()->create(['joined_on' => null, 'left_on' => null]);

        $this->getJson("/api/people/{$dated->id}")
            ->assertJsonPath('data.joined_on', '2021-03-12')
            ->assertJsonPath('data.left_on', '2024-06-03');
        $this->getJson("/api/people/{$undated->id}")
            ->assertJsonPath('data.joined_on', null)
            ->assertJsonPath('data.left_on', null);
    }

    public function test_personal_contact_details_are_not_exposed(): void
    {
        $person = Person::factory()->create();

        foreach (['email', 'phone', 'location', 'job_title', 'company'] as $field) {
            $this->getJson("/api/people/{$person->id}")->assertJsonMissingPath("data.{$field}");
        }
    }

    public function test_it_filters_by_category_slug(): void
    {
        $moderator = Category::factory()->create(['name' => 'Moderator', 'slug' => 'moderator']);
        $former = Category::factory()->create(['name' => 'Former Moderator', 'slug' => 'former-moderator']);
        Person::factory()->hasAttached($moderator)->create();
        $person = Person::factory()->hasAttached($former)->create();

        $this->getJson('/api/people?category=former-moderator')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $person->id)
            ->assertJsonPath('data.0.categories', [['name' => 'Former Moderator', 'slug' => 'former-moderator']]);
    }

    public function test_a_person_with_several_categories_appears_under_each_with_all_listed_in_order(): void
    {
        $second = Category::factory()->create(['name' => 'Translator', 'slug' => 'translator', 'sort_order' => 2]);
        $first = Category::factory()->create(['name' => 'Moderator', 'slug' => 'moderator', 'sort_order' => 1]);
        $person = Person::factory()->hasAttached([$second, $first])->create();

        foreach (['moderator', 'translator'] as $slug) {
            $this->getJson("/api/people?category={$slug}")
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $person->id)
                ->assertJsonPath('data.0.categories', [
                    ['name' => 'Moderator', 'slug' => 'moderator'],
                    ['name' => 'Translator', 'slug' => 'translator'],
                ]);
        }

        $this->getJson("/api/people/{$person->id}")->assertJsonCount(2, 'data.categories');
    }

    public function test_listing_does_not_lazy_load_categories(): void
    {
        Person::factory()->count(3)->hasAttached(Category::factory()->create())->create();
        Model::preventLazyLoading();

        try {
            $this->getJson('/api/people')->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_invalid_list_parameters_are_rejected(): void
    {
        $this->getJson('/api/people?per_page=101')->assertUnprocessable()->assertJsonValidationErrors('per_page');
        $this->getJson('/api/people?per_page=0')->assertUnprocessable()->assertJsonValidationErrors('per_page');
        $this->getJson('/api/people?category=does-not-exist')->assertUnprocessable()->assertJsonValidationErrors('category');
        $this->getJson('/api/people?page=0')->assertUnprocessable()->assertJsonValidationErrors('page');
        $this->getJson('/api/people?page=99999999999999')->assertUnprocessable()->assertJsonValidationErrors('page');
    }

    public function test_it_shows_one_person(): void
    {
        $person = Person::factory()->create(['display_name' => 'Grace Hopper']);

        $this->getJson("/api/people/{$person->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $person->id)
            ->assertJsonPath('data.display_name', 'Grace Hopper');
    }

    public function test_a_missing_person_returns_json_404(): void
    {
        $this->getJson('/api/people/999')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_malformed_ids_return_404_rather_than_a_database_error(): void
    {
        $this->getJson('/api/people/abc')->assertNotFound();
        $this->getJson('/api/people/99999999999999999999999')->assertNotFound();
    }

    public function test_avatar_url_is_absolute_or_null(): void
    {
        Storage::fake('public');
        $with = Person::factory()->create(['avatar_path' => 'avatars/a.jpg']);
        $without = Person::factory()->create(['avatar_path' => null]);

        $this->getJson("/api/people/{$with->id}")
            ->assertJsonPath('data.avatar_url', Storage::disk('public')->url('avatars/a.jpg'));
        $this->getJson("/api/people/{$without->id}")->assertJsonPath('data.avatar_url', null);
    }

    public function test_the_api_is_read_only(): void
    {
        $person = Person::factory()->create();

        $this->postJson('/api/people', [])->assertMethodNotAllowed();
        $this->putJson("/api/people/{$person->id}", [])->assertMethodNotAllowed();
        $this->deleteJson("/api/people/{$person->id}")->assertMethodNotAllowed();
    }
}
