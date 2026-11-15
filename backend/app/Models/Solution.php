<?php

namespace App\Models;

use App\Enums\Edition;
use Database\Factories\SolutionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $symptoms
 * @property string|null $cause
 * @property string $fix
 * @property Edition $edition
 * @property string|null $versions
 * @property Carbon|null $last_verified_on
 * @property bool $is_published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Tag> $tags
 * @property-read Collection<int, SolutionSignature> $signatures
 *
 * @method static Builder<static> published()
 * @method static Builder<static> search(?string $search)
 * @method static Builder<static> forEdition(?string $edition)
 * @method static Builder<static> orderedByTitle()
 */
#[Fillable(['title', 'slug', 'symptoms', 'cause', 'fix', 'edition', 'versions', 'last_verified_on', 'is_published'])]
class Solution extends Model
{
    /** @use HasFactory<SolutionFactory> */
    use HasFactory;

    // Typos still find a title once this share of its letter triples match.
    private const TYPO_SIMILARITY = 0.4;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['edition' => Edition::class, 'last_verified_on' => 'date', 'is_published' => 'boolean'];
    }

    // The slug only follows the title when it's left empty, so shared links survive renames.
    protected static function booted(): void
    {
        static::saving(function (Solution $solution) {
            if (blank($solution->slug)) {
                $solution->slug = static::uniqueSlug($solution->title, $solution->id);
            }
        });
    }

    private static function uniqueSlug(string $title, ?int $ignoreId): string
    {
        $base = Str::slug($title) ?: 'solution';
        $taken = static::query()
            ->where(fn (Builder $query) => $query->where('slug', $base)->orWhere('slug', 'like', "{$base}-%"))
            ->when($ignoreId, fn (Builder $query, int $id) => $query->whereKeyNot($id))
            ->pluck('slug');

        $slug = $base;
        for ($n = 2; $taken->contains($slug); $n++) {
            $slug = "{$base}-{$n}";
        }

        return $slug;
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->orderBy('name');
    }

    /** @return HasMany<SolutionSignature, $this> */
    public function signatures(): HasMany
    {
        return $this->hasMany(SolutionSignature::class);
    }

    /** @param  Builder<Solution>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Full-text matches rank by relevance, and close spellings of title words still count.
     *
     * @param  Builder<Solution>  $query
     */
    public function scopeSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $tsQuery = "websearch_to_tsquery('english', ?)";
        $query
            ->where(fn (Builder $query) => $query
                ->whereRaw("search_vector @@ {$tsQuery}", [$search])
                ->orWhereRaw('word_similarity(lower(?), lower(title)) >= ?', [$search, self::TYPO_SIMILARITY]))
            ->orderByRaw("ts_rank(search_vector, {$tsQuery}) desc", [$search])
            ->orderByRaw('word_similarity(lower(?), lower(title)) desc', [$search]);
    }

    /**
     * Entries for both editions apply to either one.
     *
     * @param  Builder<Solution>  $query
     */
    public function scopeForEdition(Builder $query, ?string $edition): void
    {
        if ($edition) {
            $query->whereIn('edition', [$edition, Edition::Both->value]);
        }
    }

    /**
     * Postgres on Alpine sorts by byte value, which would put "adam" after "Zed".
     *
     * @param  Builder<Solution>  $query
     */
    public function scopeOrderedByTitle(Builder $query): void
    {
        $query->orderByRaw('lower(title)')->orderBy('id');
    }

    /**
     * Signatures are plain text matched without case, so a pasted log can't trigger anything costly.
     *
     * @return Collection<int, Solution>
     */
    public static function matchingLog(string $log, int $limit = 10): Collection
    {
        $log = mb_strtolower($log);

        $hits = SolutionSignature::query()
            ->whereRelation('solution', 'is_published', true)
            ->get(['solution_id', 'pattern'])
            ->filter(fn (SolutionSignature $signature) => str_contains($log, mb_strtolower($signature->pattern)))
            ->countBy('solution_id');

        return static::query()
            ->with('tags')
            ->whereKey($hits->keys())
            ->get()
            ->sortBy([
                fn (Solution $a, Solution $b) => $hits[$b->id] <=> $hits[$a->id],
                fn (Solution $a, Solution $b) => strcasecmp($a->title, $b->title),
            ])
            ->take($limit)
            ->values();
    }
}
