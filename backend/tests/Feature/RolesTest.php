<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\People\Pages\CreatePerson;
use App\Filament\Resources\People\Pages\EditPerson;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Category;
use App\Models\Person;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    private function editor(): User
    {
        return User::factory()->create(['role' => UserRole::Editor]);
    }

    public function test_the_first_user_is_an_admin_and_later_ones_are_editors(): void
    {
        $first = User::create(['name' => 'First', 'username' => 'first', 'password' => 'password-password']);
        $second = User::create(['name' => 'Second', 'username' => 'second', 'password' => 'password-password']);

        $this->assertSame(UserRole::Admin, $first->fresh()->role);
        $this->assertSame(UserRole::Editor, $second->fresh()->role);
    }

    public function test_the_create_user_command_makes_the_first_account_an_admin(): void
    {
        Artisan::call('user:create', ['--name' => 'Boss', '--username' => 'boss', '--password' => 'long-enough-password']);

        $this->assertSame(UserRole::Admin, User::firstWhere('username', 'boss')->role);
    }

    public function test_editors_cannot_open_admin_only_pages(): void
    {
        $this->actingAs($this->editor());

        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/categories')->assertForbidden();
        $this->get('/admin/people')->assertOk();
    }

    public function test_editors_cannot_use_admin_only_pages_directly(): void
    {
        $this->actingAs($this->editor());

        Livewire::test(ManageUsers::class)->assertForbidden();
        Livewire::test(ManageCategories::class)->assertForbidden();
    }

    public function test_editors_do_not_see_admin_only_pages_in_the_sidebar(): void
    {
        $this->actingAs($this->editor());

        $this->get('/admin/people')
            ->assertSee('People')
            ->assertDontSee('/admin/users', false)
            ->assertDontSee('/admin/categories', false);
    }

    public function test_editors_can_add_and_edit_people(): void
    {
        $this->actingAs($this->editor());
        $category = Category::factory()->create();

        Livewire::test(CreatePerson::class)
            ->fillForm(['display_name' => 'Newbie', 'categories' => [$category->id]])
            ->call('create')
            ->assertHasNoFormErrors();

        $person = Person::firstWhere('display_name', 'Newbie');

        Livewire::test(EditPerson::class, ['record' => $person->getRouteKey()])
            ->fillForm(['display_name' => 'Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed', $person->fresh()->display_name);
    }

    public function test_policies_refuse_editors_on_every_admin_action(): void
    {
        $editor = $this->editor();
        $other = $this->admin();
        $category = Category::factory()->create();

        foreach (['viewAny', 'create', 'deleteAny'] as $ability) {
            $this->assertFalse($editor->can($ability, User::class), "users {$ability}");
            $this->assertFalse($editor->can($ability, Category::class), "categories {$ability}");
        }
        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertFalse($editor->can($ability, $other), "user {$ability}");
            $this->assertFalse($editor->can($ability, $category), "category {$ability}");
        }
        $this->assertFalse($editor->can('reorder', Category::class));
    }

    public function test_admins_can_choose_a_role_when_adding_someone(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: ['name' => 'Co', 'username' => 'coadmin', 'password' => 'long-enough-password', 'role' => 'admin'])
            ->assertHasNoActionErrors();

        $this->assertSame(UserRole::Admin, User::firstWhere('username', 'coadmin')->role);
    }

    public function test_new_users_default_to_editor_in_the_form(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: ['name' => 'Ed', 'username' => 'editor1', 'password' => 'long-enough-password'])
            ->assertHasNoActionErrors();

        $this->assertSame(UserRole::Editor, User::firstWhere('username', 'editor1')->role);
    }

    public function test_admins_can_change_someone_elses_role(): void
    {
        $this->actingAs($this->admin());
        $user = $this->editor();

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('edit')->table($user), data: ['role' => 'admin'])
            ->assertHasNoActionErrors();

        $this->assertSame(UserRole::Admin, $user->fresh()->role);
    }

    public function test_admins_cannot_demote_themselves_so_an_admin_always_remains(): void
    {
        $me = $this->admin();
        $this->actingAs($me);

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('edit')->table($me), data: ['name' => 'Still Me', 'role' => 'editor']);

        $this->assertSame(UserRole::Admin, $me->fresh()->role);
    }

    public function test_admins_cannot_delete_themselves_even_past_the_hidden_button(): void
    {
        $me = $this->admin();

        $this->assertFalse($me->can('delete', $me));
        $this->assertTrue($me->can('delete', $this->editor()));
    }

    public function test_an_invalid_role_is_rejected(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: ['name' => 'X', 'username' => 'x', 'password' => 'long-enough-password', 'role' => 'superuser'])
            ->assertHasActionErrors(['role']);
    }

    public function test_an_editor_cannot_promote_themselves_from_their_profile(): void
    {
        $editor = $this->editor();
        $this->actingAs($editor);

        $this->get('/admin/profile')->assertOk()->assertDontSee('data.role', false);
        $this->assertSame(UserRole::Editor, $editor->fresh()->role);
    }

    public function test_role_is_not_exposed_by_the_public_api(): void
    {
        $this->admin();

        $this->assertStringNotContainsString('role', $this->getJson('/api/categories')->getContent());
    }
}
