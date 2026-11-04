<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class UsernameLoginTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    private function user(string $username = 'ada'): User
    {
        return User::factory()->create(['username' => $username, 'password' => self::PASSWORD]);
    }

    public function test_no_email_is_stored_anywhere_for_accounts(): void
    {
        $this->assertFalse(Schema::hasColumn('users', 'email'));
        $this->assertFalse(Schema::hasColumn('users', 'email_verified_at'));
        $this->assertFalse(Schema::hasTable('password_reset_tokens'));
    }

    public function test_the_login_page_asks_for_a_username(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Username')
            ->assertDontSee('Email address');
    }

    public function test_logging_in_with_a_username_works(): void
    {
        $user = $this->user();

        Livewire::test(Login::class)
            ->fillForm(['username' => 'ada', 'password' => self::PASSWORD])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_usernames_are_case_insensitive_at_login(): void
    {
        $user = $this->user();

        Livewire::test(Login::class)
            ->fillForm(['username' => ' ADA ', 'password' => self::PASSWORD])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_shows_an_error_on_the_username_field(): void
    {
        $this->user();

        Livewire::test(Login::class)
            ->fillForm(['username' => 'ada', 'password' => 'wrong-password'])
            ->call('authenticate')
            ->assertHasFormErrors(['username']);

        $this->assertGuest();
    }

    public function test_login_attempts_are_still_rate_limited(): void
    {
        $this->user();
        RateLimiter::clear('');

        $page = Livewire::test(Login::class);
        for ($i = 0; $i < 5; $i++) {
            $page->fillForm(['username' => 'ada', 'password' => 'wrong-password'])->call('authenticate');
        }

        $page->fillForm(['username' => 'ada', 'password' => self::PASSWORD])->call('authenticate');

        $this->assertGuest();
    }

    public function test_the_authenticator_app_is_labelled_with_the_username(): void
    {
        $this->assertSame('ada', $this->user()->getAppAuthenticationHolderName());
    }

    public function test_the_profile_changes_name_and_password_but_has_no_username_or_email(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->assertSchemaComponentDoesNotExist('email', 'form')
            ->assertSchemaComponentDoesNotExist('username', 'form')
            ->fillForm(['name' => 'Ada Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Ada Renamed', $user->fresh()->name);
        $this->assertSame('ada', $user->fresh()->username);
    }

    public function test_the_command_rejects_bad_input(): void
    {
        $this->user();

        foreach ([
            ['--name' => 'A', '--username' => 'Bad Name!', '--password' => self::PASSWORD],
            ['--name' => 'A', '--username' => 'ab', '--password' => self::PASSWORD],
            ['--name' => 'A', '--username' => 'ada', '--password' => self::PASSWORD],
            ['--name' => 'A', '--username' => 'newone', '--password' => 'short'],
            ['--name' => '', '--username' => 'newone', '--password' => self::PASSWORD],
        ] as $options) {
            $this->assertSame(1, Artisan::call('user:create', $options + ['--no-interaction' => true]));
        }

        $this->assertSame(1, User::count());
    }

    public function test_the_migration_turns_existing_emails_into_unique_usernames(): void
    {
        $migration = require database_path('migrations/2026_11_05_000007_replace_email_with_username_on_users_table.php');
        $migration->down();

        $now = now();
        foreach (['Ada.L+tag@one.example', 'ada.l+tag@two.example', 'x@three.example'] as $email) {
            DB::table('users')->insert(['name' => 'n', 'email' => $email, 'password' => 'hash', 'role' => UserRole::Admin->value, 'created_at' => $now, 'updated_at' => $now]);
        }

        $migration->up();

        $this->assertSame(
            ['ada.l-tag', 'ada.l-tag-2', 'x-user'],
            DB::table('users')->orderBy('id')->pluck('username')->all(),
        );
        $this->assertSame(0, DB::table('users')->where('role', '!=', 'admin')->count());
    }
}
