<?php

namespace App\Http\Controllers\Api;

use App\Enums\Edition;
use App\Http\Controllers\Controller;
use App\Http\Resources\SolutionResource;
use App\Models\Solution;
use App\Services\McLogs;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class SolutionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'tag' => ['nullable', 'string', Rule::exists('tags', 'slug')],
            'edition' => ['nullable', Rule::in([Edition::Java->value, Edition::Bedrock->value])],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $solutions = Solution::query()
            ->published()
            ->with('tags')
            ->search($validated['search'] ?? null)
            ->forEdition($validated['edition'] ?? null)
            ->when($validated['tag'] ?? null, fn ($query, $slug) => $query->whereRelation('tags', 'slug', $slug))
            ->orderedByTitle()
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return SolutionResource::collection($solutions);
    }

    public function show(string $slug): SolutionResource
    {
        return new SolutionResource(Solution::published()->with('tags')->where('slug', $slug)->firstOrFail());
    }

    public function match(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'url' => [
                'required',
                'string',
                'max:200',
                fn (string $attribute, mixed $value, Closure $fail) => McLogs::idFromLink((string) $value) === null
                    ? $fail('Paste a link to a log on mclo.gs, like https://mclo.gs/abc123.')
                    : null,
            ],
        ]);

        $log = McLogs::raw(McLogs::idFromLink($validated['url']));

        return SolutionResource::collection(Solution::matchingLog($log));
    }
}
