<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MarketplaceController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('welcome', [
            'categories' => Category::query()
                ->active()
                ->ordered()
                ->get(['id', 'name', 'slug', 'description']),
            'featuredServices' => Service::query()
                ->published()
                ->with(['seller:id,name', 'category:id,name,slug'])
                ->latest('published_at')
                ->limit(6)
                ->get(),
        ]);
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => $request->string('q')->toString(),
            'category' => $request->string('category')->toString(),
            'price_min' => $request->string('price_min')->toString(),
            'price_max' => $request->string('price_max')->toString(),
            'sort' => $request->string('sort')->toString() ?: 'newest',
        ];

        $selectedCategory = $filters['category'] !== ''
            ? Category::query()
                ->active()
                ->where('slug', $filters['category'])
                ->first(['id', 'name', 'slug', 'description'])
            : null;

        return Inertia::render('marketplace/index', [
            'services' => $this->serviceQuery($filters)->paginate(12)->withQueryString(),
            'categories' => Category::query()
                ->active()
                ->ordered()
                ->get(['id', 'name', 'slug']),
            'filters' => $filters,
            'selectedCategory' => $selectedCategory,
        ]);
    }

    public function category(Request $request, Category $category): Response
    {
        abort_unless($category->is_active, 404);

        $filters = [
            'q' => $request->string('q')->toString(),
            'category' => $category->slug,
            'price_min' => $request->string('price_min')->toString(),
            'price_max' => $request->string('price_max')->toString(),
            'sort' => $request->string('sort')->toString() ?: 'newest',
        ];

        return Inertia::render('marketplace/index', [
            'services' => $this->serviceQuery($filters)->paginate(12)->withQueryString(),
            'categories' => Category::query()
                ->active()
                ->ordered()
                ->get(['id', 'name', 'slug']),
            'filters' => $filters,
            'selectedCategory' => $category->only(['id', 'name', 'slug', 'description']),
        ]);
    }

    public function show(Service $service): Response
    {
        abort_unless($service->isPublished(), 404);

        $service->load(['seller:id,name,created_at', 'category:id,name,slug']);

        $related = Service::query()
            ->published()
            ->where('category_id', $service->category_id)
            ->whereKeyNot($service->id)
            ->with(['seller:id,name', 'category:id,name,slug'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        return Inertia::render('marketplace/show', [
            'service' => $service,
            'related' => $related,
            'canonical' => route('services.show', $service),
        ]);
    }

    public function seller(User $user): Response
    {
        abort_unless($user->isSeller(), 404);

        $services = Service::query()
            ->published()
            ->where('seller_id', $user->id)
            ->with('category:id,name,slug')
            ->latest('published_at')
            ->get();

        return Inertia::render('marketplace/seller', [
            'seller' => $user->only(['id', 'name', 'created_at']),
            'services' => $services,
        ]);
    }

    /**
     * @param  array{q?: string, category?: string, price_min?: string, price_max?: string, sort?: string}  $filters
     */
    private function serviceQuery(array $filters): Builder
    {
        $query = Service::query()
            ->published()
            ->with(['seller:id,name', 'category:id,name,slug'])
            ->search($filters['q'] ?? null);

        if (($filters['category'] ?? '') !== '') {
            $query->whereHas('category', fn ($query) => $query->where('slug', $filters['category']));
        }

        if (($filters['price_min'] ?? '') !== '') {
            $query->where('price_minor', '>=', (int) round(((float) $filters['price_min']) * 100));
        }

        if (($filters['price_max'] ?? '') !== '') {
            $query->where('price_minor', '<=', (int) round(((float) $filters['price_max']) * 100));
        }

        return match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('price_minor'),
            'price_desc' => $query->orderByDesc('price_minor'),
            default => $query->latest('published_at'),
        };
    }
}
