<?php

namespace App\Models;

use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Intl\Countries;

/**
 * @property int $id
 * @property string $display_name
 * @property string|null $country_code
 * @property string|null $website
 * @property string|null $bio
 * @property string|null $avatar_path
 * @property Carbon|null $joined_on
 * @property Carbon|null $left_on
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $avatar_url
 * @property-read string|null $country_name
 * @property-read Collection<int, Category> $categories
 *
 * @method static Builder<static> search(?string $search)
 * @method static Builder<static> orderedByName(string $direction = 'asc')
 * @method static Builder<static> orderedByJoined(string $direction = 'asc')
 */
#[Fillable(['display_name', 'country_code', 'website', 'bio', 'avatar_path', 'joined_on', 'left_on'])]
class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['joined_on' => 'date', 'left_on' => 'date'];
    }

    // Filament leaves replaced uploads on disk, so clean them up here.
    protected static function booted(): void
    {
        static::updated(function (Person $person) {
            if ($person->wasChanged('avatar_path') && $old = $person->getOriginal('avatar_path')) {
                Storage::disk('public')->delete($old);
            }
        });

        static::deleted(function (Person $person) {
            if ($person->avatar_path) {
                Storage::disk('public')->delete($person->avatar_path);
            }
        });
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->orderBy('sort_order');
    }

    /** @return Attribute<string|null, never> */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null);
    }

    /** @return Attribute<string|null, never> */
    protected function countryName(): Attribute
    {
        return Attribute::get(fn () => $this->country_code ? Countries::getName($this->country_code) : null);
    }

    /**
     * Each word must match the name or a country, so "ada love" finds Ada Lovelace.
     *
     * @param  Builder<Person>  $query
     */
    public function scopeSearch(Builder $query, ?string $search): void
    {
        foreach (preg_split('/\s+/', trim((string) $search), flags: PREG_SPLIT_NO_EMPTY) as $term) {
            // Country names aren't stored, so turn the word into the codes it could mean.
            $countryCodes = array_keys(array_filter(
                Countries::getNames(),
                fn (string $name) => mb_stripos($name, $term) !== false,
            ));

            $query->where(function (Builder $query) use ($term, $countryCodes): void {
                $query->whereLike('display_name', "%{$term}%")
                    ->orWhereIn('country_code', $countryCodes);
            });
        }
    }

    /**
     * Postgres on Alpine sorts by byte value, which would put "adam" after "Zed".
     *
     * @param  Builder<Person>  $query
     */
    public function scopeOrderedByName(Builder $query, string $direction = 'asc'): void
    {
        $query->orderByRaw('lower(display_name) '.($direction === 'desc' ? 'desc' : 'asc'));
    }

    /**
     * Undated people go last in both directions, with ties settled by name.
     *
     * @param  Builder<Person>  $query
     */
    public function scopeOrderedByJoined(Builder $query, string $direction = 'asc'): void
    {
        $query->orderByRaw('joined_on '.($direction === 'desc' ? 'desc' : 'asc').' nulls last')->orderedByName();
    }
}
