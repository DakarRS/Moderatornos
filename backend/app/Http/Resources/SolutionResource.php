<?php

namespace App\Http\Resources;

use App\Models\Solution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/** @mixin Solution */
class SolutionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'symptoms' => $this->symptoms,
            'cause' => $this->cause,
            'fix' => $this->fix,
            'html' => [
                'symptoms' => self::markdown($this->symptoms),
                'cause' => self::markdown($this->cause),
                'fix' => self::markdown($this->fix),
            ],
            'edition' => $this->edition->value,
            'versions' => $this->versions,
            'tags' => $this->tags->map(fn ($tag) => ['name' => $tag->name, 'slug' => $tag->slug]),
            'last_verified_on' => $this->last_verified_on?->toDateString(),
            'updated_at' => $this->updated_at,
        ];
    }

    // The frontend inserts this as HTML, so raw tags are escaped and script links dropped.
    private static function markdown(?string $text): ?string
    {
        return filled($text) ? Str::markdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false]) : null;
    }
}
