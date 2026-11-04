<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string|null $label
 * @property UserRole $role
 * @property string $token
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 * @property int|null $created_by
 * @property int|null $used_by
 * @property-read string $link
 * @property-read User|null $creator
 * @property-read User|null $usedBy
 */
#[Fillable(['label', 'role', 'token', 'token_hash', 'expires_at', 'used_at', 'created_by', 'used_by'])]
#[Hidden(['token', 'token_hash'])]
class Invitation extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            // Encrypted so admins can copy the link again, and hashed so it can be looked up.
            'token' => 'encrypted',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /** @param  array{label?: string|null, role?: UserRole|string, expires_in_days?: int|string}  $data */
    public static function issue(array $data): self
    {
        $token = Str::random(64);

        return self::create([
            'label' => $data['label'] ?? null,
            'role' => $data['role'] ?? UserRole::Editor,
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays((int) ($data['expires_in_days'] ?? 7)),
            'created_by' => auth()->id(),
        ]);
    }

    /** @return Builder<self> */
    public static function usable(string $token): Builder
    {
        return self::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')
            ->where('expires_at', '>', now());
    }

    public function isPending(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    /** @return Attribute<string, never> */
    protected function link(): Attribute
    {
        return Attribute::get(fn (): string => route('filament.admin.auth.register', ['invite' => $this->token]));
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}
