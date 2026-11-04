<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Rules\UniqueUsername;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $username
 * @property UserRole $role
 */
#[Fillable(['username', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, Notifiable;

    // Accounts only come from an admin or the CLI, so the first one has to be able to manage the rest.
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->role ??= static::query()->exists() ? UserRole::Editor : UserRole::Admin;
        });
    }

    public const USERNAME_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9._-]{2,49}$/';

    public const USERNAME_MESSAGE = 'Use 3 to 50 letters, numbers, dots, dashes or underscores, starting with a letter or number.';

    /** @return list<mixed> */
    public static function usernameRules(?self $ignore = null): array
    {
        return [
            'required',
            'string',
            'regex:'.self::USERNAME_PATTERN,
            new UniqueUsername($ignore),
        ];
    }

    public function getFilamentName(): string
    {
        return $this->username;
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->username;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    // There is no public registration, so every account may use the panel. Policies limit what each role can do.
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }
}
