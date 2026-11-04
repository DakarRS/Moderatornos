<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueUsername implements ValidationRule
{
    public function __construct(private ?User $ignore = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $taken = User::query()
            ->whereRaw('lower(username) = ?', [mb_strtolower((string) $value)])
            ->when($this->ignore, fn ($query, User $user) => $query->whereKeyNot($user->getKey()))
            ->exists();

        if ($taken) {
            $fail('This username is already taken.');
        }
    }
}
