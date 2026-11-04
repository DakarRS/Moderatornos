<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Auth\Register;
use App\Filament\Resources\Invitations\Pages\ManageInvitations;
use App\Models\Invitation;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    private function invite(UserRole $role = UserRole::Editor, int $days = 7): Invitation
    {
        return Invitation::issue(['label' => 'For someone', 'role' => $role, 'expires_in_days' => $days]);
    }

    private function registerPage(Invitation $invitation)
    {
        return Livewire::withQueryParams(['invite' => $invitation->token])->test(Register::class);
    }

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'username' => 'newperson',
            'password' => self::PASSWORD,
            'passwordConfirmation' => self::PASSWORD,
        ];
    }

    public function test_registration_is_closed_without_a_valid_link(): void
    {
        $this->get('/admin/register')->assertNotFound();
        $this->get('/admin/register?invite=made-up')->assertNotFound();
    }

    public function test_expired_used_and_revoked_links_do_not_open(): void
    {
        $expired = $this->invite();
        $expired->update(['expires_at' => now()->subMinute()]);
        $used = $this->invite();
        $used->update(['used_at' => now()]);
        $revoked = $this->invite();
        $link = $revoked->link;
        $revoked->delete();

        $this->get($expired->link)->assertNotFound();
        $this->get($used->link)->assertNotFound();
        $this->get($link)->assertNotFound();
    }

    public function test_a_valid_link_opens_without_asking_for_an_email(): void
    {
        $this->get($this->invite()->link)
            ->assertOk()
            ->assertSee('Username')
            ->assertDontSee('Email address');
    }

    public function test_the_login_page_does_not_advertise_registration(): void
    {
        $this->get('/admin/login')->assertOk()->assertDontSee('/admin/register', false);
    }

    public function test_registering_creates_the_account_uses_up_the_link_and_logs_in(): void
    {
        $invitation = $this->invite();

        $this->registerPage($invitation)
            ->fillForm($this->form())
            ->call('register')
            ->assertHasNoFormErrors();

        $user = User::firstWhere('username', 'newperson');
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($invitation->fresh()->used_at);
        $this->assertTrue($invitation->fresh()->usedBy->is($user));
    }

    public function test_the_role_comes_from_the_invitation(): void
    {
        $this->registerPage($this->invite(UserRole::Admin))->fillForm($this->form())->call('register');
        $this->assertSame(UserRole::Admin, User::firstWhere('username', 'newperson')->role);

        auth()->logout();

        $this->registerPage($this->invite(UserRole::Editor))
            ->fillForm($this->form(['username' => 'another', 'role' => 'admin']))
            ->call('register');
        $this->assertSame(UserRole::Editor, User::firstWhere('username', 'another')->role);
    }

    public function test_a_link_cannot_be_used_twice(): void
    {
        $invitation = $this->invite();
        $this->registerPage($invitation)->fillForm($this->form())->call('register');
        auth()->logout();

        $this->get($invitation->link)->assertNotFound();
    }

    public function test_a_link_used_up_while_the_form_was_open_is_refused(): void
    {
        $invitation = $this->invite();
        $page = $this->registerPage($invitation)->fillForm($this->form());

        $invitation->update(['used_at' => now()]);
        $page->call('register')->assertHasFormErrors(['username']);

        $this->assertNull(User::firstWhere('username', 'newperson'));
        $this->assertGuest();
    }

    public function test_the_token_on_the_form_cannot_be_swapped_for_another(): void
    {
        $page = $this->registerPage($this->invite());

        $this->expectException(\Exception::class);
        $page->set('invite', 'something-else');
    }

    public function test_registration_validates_input_and_leaves_the_link_unused(): void
    {
        User::factory()->create(['username' => 'taken']);
        $invitation = $this->invite();

        $this->registerPage($invitation)
            ->fillForm($this->form(['username' => 'Not Valid!']))->call('register')
            ->assertHasFormErrors(['username']);
        $this->registerPage($invitation)
            ->fillForm($this->form(['username' => 'taken']))->call('register')
            ->assertHasFormErrors(['username']);
        $this->registerPage($invitation)
            ->fillForm($this->form(['password' => 'short', 'passwordConfirmation' => 'short']))->call('register')
            ->assertHasFormErrors(['password']);

        $this->assertNull($invitation->fresh()->used_at);
    }

    public function test_a_short_password_shows_one_error_not_two(): void
    {
        $page = $this->registerPage($this->invite())
            ->fillForm($this->form(['password' => 'abc', 'passwordConfirmation' => 'abc']))
            ->call('register');

        $this->assertSame(
            ['The password field must be at least 8 characters.'],
            $page->errors()->get('data.password'),
        );
    }

    public function test_capital_letters_in_a_username_are_kept(): void
    {
        $this->registerPage($this->invite())
            ->fillForm($this->form(['username' => 'Dobbie']))
            ->call('register')
            ->assertHasNoFormErrors();

        $this->assertNotNull(User::firstWhere('username', 'Dobbie'));
    }

    public function test_a_username_differing_only_by_case_is_refused(): void
    {
        User::factory()->create(['username' => 'dobbie']);

        $this->registerPage($this->invite())
            ->fillForm($this->form(['username' => 'Dobbie']))
            ->call('register')
            ->assertHasFormErrors(['username']);

        $this->assertSame(1, User::count());
    }

    public function test_an_invalid_username_explains_what_is_allowed(): void
    {
        $page = $this->registerPage($this->invite())
            ->fillForm($this->form(['username' => 'no spaces!']))
            ->call('register');

        $this->assertSame(
            ['Use 3 to 50 letters, numbers, dots, dashes or underscores, starting with a letter or number.'],
            $page->errors()->get('data.username'),
        );
    }

    public function test_passwords_need_eight_characters(): void
    {
        $this->registerPage($this->invite())
            ->fillForm($this->form(['password' => 'sevench', 'passwordConfirmation' => 'sevench']))
            ->call('register')
            ->assertHasFormErrors(['password']);
        $this->assertSame(0, User::count());

        $this->registerPage($this->invite())
            ->fillForm($this->form(['password' => 'eightchr', 'passwordConfirmation' => 'eightchr']))
            ->call('register')
            ->assertHasNoFormErrors();
        $this->assertSame(1, User::count());
    }

    public function test_registration_attempts_are_rate_limited(): void
    {
        $page = $this->registerPage($this->invite());

        for ($i = 0; $i < 10; $i++) {
            $page->fillForm($this->form(['username' => 'Not Valid!']))->call('register')->assertHasFormErrors(['username']);
        }

        $page->fillForm($this->form())->call('register');
        $this->assertNull(User::firstWhere('username', 'newperson'));
    }

    public function test_tokens_are_not_stored_in_readable_form(): void
    {
        $invitation = $this->invite();

        $row = DB::table('invitations')->find($invitation->id);
        $this->assertStringNotContainsString($invitation->token, $row->token);
        $this->assertSame(hash('sha256', $invitation->token), $row->token_hash);
        $this->assertArrayNotHasKey('token', $invitation->toArray());
        $this->assertArrayNotHasKey('token_hash', $invitation->toArray());
    }

    public function test_tokens_are_long_and_unique(): void
    {
        $this->assertGreaterThanOrEqual(40, strlen($this->invite()->token));
        $this->assertNotSame($this->invite()->token, $this->invite()->token);
    }

    public function test_an_admin_can_create_an_invitation_with_a_role_and_expiry(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Livewire::test(ManageInvitations::class)
            ->callAction('create', data: ['label' => 'For Ada', 'role' => 'admin', 'expires_in_days' => 30])
            ->assertHasNoActionErrors();

        $invitation = Invitation::firstWhere('label', 'For Ada');
        $this->assertSame(UserRole::Admin, $invitation->role);
        $this->assertTrue($invitation->creator->is($admin));
        $this->assertEqualsWithDelta(30, now()->diffInDays($invitation->expires_at), 0.01);
    }

    public function test_invitations_default_to_an_editor_for_seven_days(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageInvitations::class)->callAction('create')->assertHasNoActionErrors();

        $invitation = Invitation::first();
        $this->assertSame(UserRole::Editor, $invitation->role);
        $this->assertEqualsWithDelta(7, now()->diffInDays($invitation->expires_at), 0.01);
    }

    public function test_an_admin_can_get_the_link_again_and_revoke_only_unused_invitations(): void
    {
        $this->actingAs(User::factory()->create());
        $pending = $this->invite();
        $used = $this->invite();
        $used->update(['used_at' => now()]);

        Livewire::test(ManageInvitations::class)
            ->assertActionVisible(TestAction::make('link')->table($pending))
            ->assertActionHidden(TestAction::make('link')->table($used))
            ->assertActionHidden(TestAction::make('delete')->table($used))
            ->callAction(TestAction::make('delete')->table($pending));

        $this->assertModelMissing($pending);
        $this->assertModelExists($used);
    }

    public function test_editors_cannot_manage_invitations(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $this->actingAs($editor);

        $this->get('/admin/invitations')->assertForbidden();
        $this->assertFalse($editor->can('create', Invitation::class));
        $this->assertFalse($editor->can('viewAny', Invitation::class));
    }

    public function test_deleting_the_person_who_sent_an_invite_keeps_the_invite_working(): void
    {
        $sender = User::factory()->create();
        $this->actingAs($sender);
        $invitation = $this->invite();
        auth()->logout();

        $sender->delete();

        $this->get($invitation->fresh()->link)->assertOk();
    }
}
