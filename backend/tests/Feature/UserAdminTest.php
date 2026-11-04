<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    protected function setUp(): void
    {
        parent::setUp();
        $this->me = User::factory()->create();
        $this->actingAs($this->me);
    }

    public function test_guests_cannot_reach_the_users_page(): void
    {
        auth()->logout();

        $this->get('/admin/users')->assertRedirect('/admin/login');
    }

    public function test_an_admin_can_add_a_user_who_can_then_log_in(): void
    {
        Livewire::test(ManageUsers::class)
            ->callAction('create', data: ['username' => 'ada', 'password' => 'correct-horse-battery'])
            ->assertHasNoActionErrors();

        $this->assertTrue(Hash::check('correct-horse-battery', User::firstWhere('username', 'ada')->password));
    }

    public function test_new_users_need_a_password_and_a_valid_unique_username(): void
    {
        Livewire::test(ManageUsers::class)
            ->callAction('create', data: ['username' => $this->me->username, 'password' => ''])
            ->assertHasActionErrors(['username', 'password' => 'required']);

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: ['username' => 'Not Valid!', 'password' => 'short'])
            ->assertHasActionErrors(['username', 'password']);
    }

    public function test_admins_can_use_capitals_but_not_case_only_duplicates(): void
    {
        Livewire::test(ManageUsers::class)
            ->callAction('create', data: ['username' => 'Dobbie', 'password' => 'long-enough-password'])
            ->assertHasNoActionErrors();
        $this->assertNotNull(User::firstWhere('username', 'Dobbie'));

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: ['username' => 'DOBBIE', 'password' => 'long-enough-password'])
            ->assertHasActionErrors(['username']);
    }

    public function test_changing_only_the_case_of_your_own_username_is_allowed(): void
    {
        $user = User::factory()->create(['username' => 'dobbie']);

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('edit')->table($user), data: ['username' => 'Dobbie'])
            ->assertHasNoActionErrors();

        $this->assertSame('Dobbie', $user->fresh()->username);
    }

    public function test_editing_without_a_password_keeps_the_old_one(): void
    {
        $user = User::factory()->create(['password' => 'original-password-1']);

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('edit')->table($user), data: ['username' => 'renamed', 'password' => ''])
            ->assertHasNoActionErrors();

        $this->assertSame('renamed', $user->fresh()->username);
        $this->assertTrue(Hash::check('original-password-1', $user->fresh()->password));
    }

    public function test_an_admin_can_reset_someones_password(): void
    {
        $user = User::factory()->create();

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('edit')->table($user), data: ['password' => 'brand-new-password-2'])
            ->assertHasNoActionErrors();

        $this->assertTrue(Hash::check('brand-new-password-2', $user->fresh()->password));
    }

    public function test_a_username_can_stay_the_same_when_editing(): void
    {
        $user = User::factory()->create();

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('edit')->table($user), data: ['username' => $user->username])
            ->assertHasNoActionErrors();
    }

    public function test_others_can_be_deleted_but_not_yourself(): void
    {
        $other = User::factory()->create();

        Livewire::test(ManageUsers::class)
            ->assertActionHidden(TestAction::make('delete')->table($this->me))
            ->callAction(TestAction::make('delete')->table($other));

        $this->assertModelMissing($other);
        $this->assertModelExists($this->me);
    }
}
