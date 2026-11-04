<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PersonResource;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PersonController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', Rule::exists('categories', 'slug')],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $people = Person::query()
            ->with('categories')
            ->search($validated['search'] ?? null)
            ->when($validated['category'] ?? null, fn ($query, $slug) => $query->whereRelation('categories', 'slug', $slug))
            ->orderedByJoined()
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 12)
            ->withQueryString();

        return PersonResource::collection($people);
    }

    public function show(Person $person): PersonResource
    {
        return new PersonResource($person->load('categories'));
    }
}
