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
            ->callAction('create', data: ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'correct-horse-battery'])
            ->assertHasNoActionErrors();

        $this->assertTrue(Hash::check('correct-horse-battery', User::firstWhere('email', 'ada@example.com')->password));
    }

    public function test_new_users_need_a_password_a_valid_unique_email_and_a_name(): void
    {
        Livewire::test(ManageUsers::class)
            ->callAction('create', data: ['name' => '', 'email' => $this->me->email, 'password' => ''])
            ->assertHasActionErrors(['name' => 'required', 'email', 'password' => 'required']);

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: ['name' => 'A', 'email' => 'not-an-email', 'password' => 'short'])
            ->assertHasActionErrors(['email', 'password']);
    }

    public function test_editing_without_a_password_keeps_the_old_one(): void
    {
        $user = User::factory()->create(['password' => 'original-password-1']);

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('edit')->table($user), data: ['name' => 'Renamed', 'password' => ''])
            ->assertHasNoActionErrors();

        $this->assertSame('Renamed', $user->fresh()->name);
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

    public function test_an_email_can_stay_the_same_when_editing(): void
    {
        $user = User::factory()->create();

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('edit')->table($user), data: ['email' => $user->email])
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
