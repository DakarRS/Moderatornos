<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_categories_in_configured_order_including_empty_ones(): void
    {
        Category::factory()->create(['name' => 'Second', 'slug' => 'second', 'sort_order' => 2]);
        Category::factory()->create(['name' => 'First', 'slug' => 'first', 'sort_order' => 1]);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertExactJson(['data' => [
                ['name' => 'First', 'slug' => 'first', 'people_count' => 0],
                ['name' => 'Second', 'slug' => 'second', 'people_count' => 0],
            ], 'meta' => ['total_people' => 0]]);
    }

    public function test_counts_follow_the_search(): void
    {
        $moderator = Category::factory()->create(['slug' => 'moderator', 'sort_order' => 1]);
        $former = Category::factory()->create(['slug' => 'former', 'sort_order' => 2]);
        Person::factory()->hasAttached($moderator)->count(2)->create(['country_code' => 'DE']);
        Person::factory()->hasAttached($former)->create(['country_code' => 'DE']);
        Person::factory()->hasAttached($former)->create(['country_code' => 'FR']);

        $this->getJson('/api/categories?search=germany')
            ->assertJsonPath('data.0.people_count', 2)
            ->assertJsonPath('data.1.people_count', 1);
    }

    public function test_total_people_counts_someone_in_two_categories_once(): void
    {
        $categories = Category::factory()->count(2)->create();
        Person::factory()->hasAttached($categories)->create(['country_code' => 'DE']);
        Person::factory()->hasAttached($categories->first())->create(['country_code' => 'FR']);

        $this->getJson('/api/categories')->assertJsonPath('meta.total_people', 2);
        $this->getJson('/api/categories?search=germany')->assertJsonPath('meta.total_people', 1);
    }

    public function test_the_api_is_read_only(): void
    {
        $this->postJson('/api/categories', [])->assertMethodNotAllowed();
    }
}
