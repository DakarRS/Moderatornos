<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate(['search' => ['nullable', 'string', 'max:100']]);

        $search = $validated['search'] ?? null;

        $categories = Category::query()
            ->withCount(['people' => fn ($query) => $query->search($search)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Summing the category counts would count people with several categories twice.
        return CategoryResource::collection($categories)
            ->additional(['meta' => ['total_people' => Person::query()->search($search)->count()]]);
    }
}
