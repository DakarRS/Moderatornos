<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use App\Models\Person;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_an_admin_can_add_a_category_which_goes_last(): void
    {
        Category::factory()->create(['sort_order' => 7]);

        Livewire::test(ManageCategories::class)
            ->callAction('create', data: ['name' => 'Translator', 'slug' => 'translator'])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('categories', ['slug' => 'translator', 'sort_order' => 8]);
        $this->getJson('/api/categories')->assertJsonPath('data.1.name', 'Translator');
    }

    public function test_the_slug_is_suggested_from_the_name_when_creating(): void
    {
        Livewire::test(ManageCategories::class)
            ->mountAction('create')
            ->fillForm(['name' => 'Former Translator'])
            ->assertSchemaStateSet(['slug' => 'former-translator']);
    }

    public function test_slugs_must_be_unique_and_url_safe(): void
    {
        Category::factory()->create(['slug' => 'taken']);

        Livewire::test(ManageCategories::class)
            ->callAction('create', data: ['name' => 'A', 'slug' => 'taken'])
            ->assertHasActionErrors(['slug']);

        Livewire::test(ManageCategories::class)
            ->callAction('create', data: ['name' => 'B', 'slug' => 'Not Valid/'])
            ->assertHasActionErrors(['slug']);
    }

    public function test_renaming_a_category_keeps_its_slug_and_shows_in_the_api(): void
    {
        $category = Category::factory()->create(['name' => 'Mod', 'slug' => 'moderator']);

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make('edit')->table($category), data: ['name' => 'Moderator'])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('categories', ['name' => 'Moderator', 'slug' => 'moderator']);
    }

    public function test_only_empty_categories_can_be_deleted(): void
    {
        $used = Category::factory()->create();
        Person::factory()->hasAttached($used)->create();
        $empty = Category::factory()->create();

        Livewire::test(ManageCategories::class)
            ->assertActionHidden(TestAction::make('delete')->table($used))
            ->callAction(TestAction::make('delete')->table($empty));

        $this->assertModelExists($used);
        $this->assertModelMissing($empty);
    }

    public function test_deleting_a_person_frees_their_category_for_deletion(): void
    {
        $category = Category::factory()->create();
        $person = Person::factory()->hasAttached($category)->create();

        $person->delete();

        $this->assertDatabaseMissing('category_person', ['person_id' => $person->id]);
        $this->assertFalse($category->people()->exists());
    }

    public function test_categories_can_be_reordered(): void
    {
        $first = Category::factory()->create(['sort_order' => 1]);
        $second = Category::factory()->create(['sort_order' => 2]);

        Livewire::test(ManageCategories::class)->call('reorderTable', [$second->id, $first->id]);

        $this->assertLessThan($first->fresh()->sort_order, $second->fresh()->sort_order);
    }
}
