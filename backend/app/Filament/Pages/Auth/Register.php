<?php

namespace App\Filament\Pages\Auth;

use App\Models\Invitation;
use App\Models\User;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use SensitiveParameter;

class Register extends BaseRegister
{
    #[Locked]
    public string $invite = '';

    public function mount(): void
    {
        $this->invite = (string) request()->query('invite', '');

        // A 404 rather than an error, so the page looks the same as if registration didn't exist.
        abort_unless(Invitation::usable($this->invite)->exists(), 404);

        parent::mount();
    }

    /**
     * Filament allows 2 a minute, which locks out anyone with a couple of typos. Only link holders get here.
     *
     * @param  int  $maxAttempts
     * @param  int  $decaySeconds
     * @param  string|null  $method
     * @param  string|null  $component
     */
    protected function rateLimit($maxAttempts, $decaySeconds = 60, $method = null, $component = null): void
    {
        parent::rateLimit(10, $decaySeconds, $method, $component);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('username')
                ->required()
                ->maxLength(50)
                ->rules(User::usernameRules())
                ->validationMessages(['regex' => User::USERNAME_MESSAGE])
                ->autocomplete('username')
                ->helperText('Used to log in. Letters, numbers, dots, dashes and underscores.'),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        // Locked and rechecked, so two people can't both use one link at the same moment.
        $invitation = Invitation::usable($this->invite)->lockForUpdate()->first();

        if (! $invitation) {
            throw ValidationException::withMessages([
                'data.username' => 'This invite link has expired or has already been used.',
            ]);
        }

        $user = User::create([
            'username' => $data['username'],
            'password' => $data['password'],
            'role' => $invitation->role,
        ]);

        $invitation->update(['used_at' => now(), 'used_by' => $user->id]);

        return $user;
    }
}
