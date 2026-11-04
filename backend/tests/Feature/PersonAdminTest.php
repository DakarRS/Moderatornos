<?php

namespace Tests\Feature;

use App\Filament\Resources\People\Pages\CreatePerson;
use App\Filament\Resources\People\Pages\EditPerson;
use App\Filament\Resources\People\Pages\ListPeople;
use App\Models\Category;
use App\Models\Person;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PersonAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/people')->assertRedirect('/admin/login');
    }

    public function test_the_list_page_shows_people(): void
    {
        $this->actingAs(User::factory()->create());
        $people = Person::factory()->count(2)->create();

        Livewire::test(ListPeople::class)->assertCanSeeTableRecords($people);
    }

    public function test_the_list_page_filters_by_category(): void
    {
        $this->actingAs(User::factory()->create());
        $current = Person::factory()->hasAttached(Category::factory()->create())->create();
        $category = Category::factory()->create();
        $former = Person::factory()->hasAttached($category)->create();

        Livewire::test(ListPeople::class)
            ->filterTable('categories', $category->id)
            ->assertCanSeeTableRecords([$former])
            ->assertCanNotSeeTableRecords([$current]);
    }

    public function test_the_list_page_defaults_to_the_same_order_as_the_api(): void
    {
        $this->actingAs(User::factory()->create());
        $undated = Person::factory()->create(['display_name' => 'Abbey', 'joined_on' => null]);
        $recent = Person::factory()->create(['display_name' => 'Bob', 'joined_on' => '2024-01-01']);
        $oldest = Person::factory()->create(['display_name' => 'Zed', 'joined_on' => '2015-01-01']);

        Livewire::test(ListPeople::class)->assertCanSeeTableRecords([$oldest, $recent, $undated], inOrder: true);

        // Undated people stay last when the column is flipped to newest first.
        Livewire::test(ListPeople::class)
            ->sortTable('joined_on', 'desc')
            ->assertCanSeeTableRecords([$recent, $oldest, $undated], inOrder: true);
    }

    public function test_the_list_page_sorts_ignoring_case_in_both_directions(): void
    {
        $this->actingAs(User::factory()->create());
        $bob = Person::factory()->create(['display_name' => 'Bob']);
        $adam = Person::factory()->create(['display_name' => 'adam']);

        Livewire::test(ListPeople::class)
            ->sortTable('display_name')
            ->assertCanSeeTableRecords([$adam, $bob], inOrder: true);
        Livewire::test(ListPeople::class)
            ->sortTable('display_name', 'desc')
            ->assertCanSeeTableRecords([$bob, $adam], inOrder: true);
    }

    public function test_the_list_page_searches_and_sorts_by_name(): void
    {
        $this->actingAs(User::factory()->create());
        $ada = Person::factory()->create(['display_name' => 'Ada Lovelace']);
        $grace = Person::factory()->create(['display_name' => 'Grace Hopper']);

        Livewire::test(ListPeople::class)
            ->searchTable('ada love')
            ->assertCanSeeTableRecords([$ada])
            ->assertCanNotSeeTableRecords([$grace]);

        Livewire::test(ListPeople::class)
            ->sortTable('display_name')
            ->assertCanSeeTableRecords([$ada, $grace], inOrder: true);
    }

    public function test_bulk_delete_removes_avatar_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/a.jpg', 'x');
        $this->actingAs(User::factory()->create());
        $person = Person::factory()->create(['avatar_path' => 'avatars/a.jpg']);

        Livewire::test(ListPeople::class)->callTableBulkAction('delete', [$person]);

        $this->assertModelMissing($person);
        Storage::disk('public')->assertMissing('avatars/a.jpg');
    }

    public function test_an_admin_can_create_a_person(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();

        Livewire::test(CreatePerson::class)
            ->fillForm(['display_name' => 'Ada Lovelace', 'categories' => [$category->id]])
            ->call('create')
            ->assertHasNoFormErrors();

        $person = Person::firstWhere('display_name', 'Ada Lovelace');
        $this->assertTrue($person->categories->contains($category));
        $this->assertNull($person->country_code);
        $this->assertNull($person->website);
        $this->assertNull($person->bio);
    }

    public function test_an_admin_can_edit_a_person_and_the_api_reflects_it(): void
    {
        $this->actingAs(User::factory()->create());
        $moderator = Category::factory()->create(['slug' => 'moderator', 'sort_order' => 1]);
        $translator = Category::factory()->create(['slug' => 'translator', 'sort_order' => 2]);
        $person = Person::factory()->hasAttached($moderator)->create();

        Livewire::test(EditPerson::class, ['record' => $person->getRouteKey()])
            ->fillForm(['categories' => [$moderator->id, $translator->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->getJson("/api/people/{$person->id}")->assertJsonPath('data.categories.1.slug', 'translator');
    }

    public function test_an_admin_can_set_a_country(): void
    {
        $this->actingAs(User::factory()->create());
        $person = Person::factory()->hasAttached(Category::factory()->create())->create(['country_code' => null]);

        Livewire::test(EditPerson::class, ['record' => $person->getRouteKey()])
            ->fillForm(['country_code' => 'DE'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('DE', $person->fresh()->country_code);
    }

    public function test_an_admin_can_set_joined_and_left_dates(): void
    {
        $this->actingAs(User::factory()->create());
        $person = Person::factory()->hasAttached(Category::factory()->create())->create();

        Livewire::test(EditPerson::class, ['record' => $person->getRouteKey()])
            ->fillForm(['joined_on' => '2021-03-12', 'left_on' => '2024-06-03'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('2021-03-12', $person->fresh()->joined_on->toDateString());
        $this->assertSame('2024-06-03', $person->fresh()->left_on->toDateString());
    }

    public function test_either_date_can_be_set_without_the_other(): void
    {
        $this->actingAs(User::factory()->create());
        $person = Person::factory()->hasAttached(Category::factory()->create())->create(['joined_on' => null, 'left_on' => null]);

        Livewire::test(EditPerson::class, ['record' => $person->getRouteKey()])
            ->fillForm(['joined_on' => null, 'left_on' => '2024-06-03'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertNull($person->fresh()->joined_on);

        Livewire::test(EditPerson::class, ['record' => $person->getRouteKey()])
            ->fillForm(['joined_on' => '2021-03-12', 'left_on' => null])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertNull($person->fresh()->left_on);
    }

    public function test_a_date_can_be_cleared_with_its_button_which_only_shows_when_filled(): void
    {
        $this->actingAs(User::factory()->create());
        $person = Person::factory()->hasAttached(Category::factory()->create())->create(['joined_on' => '2021-03-12', 'left_on' => null]);

        Livewire::test(EditPerson::class, ['record' => $person->getRouteKey()])
            ->assertActionVisible(TestAction::make('clear')->schemaComponent('joined_on'))
            ->assertActionHidden(TestAction::make('clear')->schemaComponent('left_on'))
            ->callAction(TestAction::make('clear')->schemaComponent('joined_on'))
            ->assertSchemaStateSet(['joined_on' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($person->fresh()->joined_on);
    }

    public function test_left_can_equal_but_not_precede_joined(): void
    {
        $this->actingAs(User::factory()->create());
        $person = Person::factory()->hasAttached(Category::factory()->create())->create();

        Livewire::test(EditPerson::class, ['record' => $person->getRouteKey()])
            ->fillForm(['joined_on' => '2024-06-03', 'left_on' => '2021-03-12'])
            ->call('save')
            ->assertHasFormErrors(['left_on']);

        Livewire::test(EditPerson::class, ['record' => $person->getRouteKey()])
            ->fillForm(['joined_on' => '2024-06-03', 'left_on' => '2024-06-03'])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_the_form_validates_input(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(CreatePerson::class)
            ->fillForm(['display_name' => '', 'categories' => [], 'country_code' => 'XX', 'website' => 'javascript:alert(1)'])
            ->call('create')
            ->assertHasFormErrors(['display_name' => 'required', 'categories' => 'required', 'country_code', 'website']);
    }

    public function test_replacing_or_deleting_an_avatar_removes_the_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old.jpg', 'x');
        Storage::disk('public')->put('avatars/new.jpg', 'x');
        $person = Person::factory()->create(['avatar_path' => 'avatars/old.jpg']);

        $person->update(['avatar_path' => 'avatars/new.jpg']);
        Storage::disk('public')->assertMissing('avatars/old.jpg');
        Storage::disk('public')->assertExists('avatars/new.jpg');

        $person->delete();
        Storage::disk('public')->assertMissing('avatars/new.jpg');
    }
}
