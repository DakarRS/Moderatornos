<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('username')
                ->required()
                ->autocomplete('username')
                ->autofocus(),
            $this->getPasswordFormComponent(),
            $this->getRememberFormComponent(),
        ]);
    }

    // Registration needs an invite link, so the login page shouldn't offer a sign-up.
    public function getSubheading(): string|Htmlable|null
    {
        return filled($this->userUndertakingMultiFactorAuthentication) ? parent::getSubheading() : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'username' => strtolower(trim($data['username'])),
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }
}
