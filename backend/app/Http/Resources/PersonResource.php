<?php

namespace App\Http\Resources;

use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Person */
class PersonResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'display_name' => $this->display_name,
            'categories' => $this->categories->map(fn ($category) => ['name' => $category->name, 'slug' => $category->slug]),
            'country' => $this->country_code ? ['code' => $this->country_code, 'name' => $this->country_name] : null,
            'website' => $this->website,
            'bio' => $this->bio,
            'avatar_url' => $this->avatar_url,
            'joined_on' => $this->joined_on?->toDateString(),
            'left_on' => $this->left_on?->toDateString(),
            'updated_at' => $this->updated_at,
        ];
    }
}
