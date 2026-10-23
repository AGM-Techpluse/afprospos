<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Domain\Inventory\Application\Contracts\StorefrontCatalogQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** The public storefront — guest-visible, cross-shop product browsing (no auth middleware; cart/checkout live under Customer\ShopController). */
final class ShopController
{
    public function __construct(private readonly StorefrontCatalogQuery $catalog) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString() ?: null;
        $category = $request->string('category')->toString() ?: null;
        $isSearching = $search !== null || $category !== null;
        $categories = $this->catalog->categories();

        return Inertia::render('Shop/Index', [
            'products' => $isSearching
                ? $this->catalog->list(search: $search, category: $category, page: $request->integer('page', 1))
                : null,
            // Browse mode (no active search/filter): a Jumia-style homepage —
            // a "Featured products" row plus one row per category, each
            // capped small since these are just previews (a "See all" link
            // switches into search mode via the `category` filter).
            'browse' => $isSearching ? null : [
                'featured' => $this->catalog->featured(8),
                'sections' => collect($categories)
                    ->map(fn (string $category): array => [
                        'category' => $category,
                        'products' => $this->catalog->list(search: null, category: $category, page: 1, perPage: 6)['data'],
                    ])
                    ->filter(fn (array $section): bool => $section['products'] !== [])
                    ->values()
                    ->all(),
            ],
            'filters' => [
                'search' => $search ?? '',
                'category' => $category ?? '',
            ],
            'categories' => $categories,
        ]);
    }

    public function show(int $sku): Response
    {
        $product = $this->catalog->find($sku);

        abort_if($product === null, 404);

        // "More like this" — a few other sellable products in the same
        // category, excluding this one. Reuses list() rather than a new
        // query method; a handful of alphabetically-first results is
        // plenty for a preview row.
        $related = collect($this->catalog->list(search: null, category: $product['category'], page: 1, perPage: 5)['data'])
            ->reject(fn (array $row): bool => $row['sku_id'] === $sku)
            ->take(4)
            ->values()
            ->all();

        return Inertia::render('Shop/Show', [
            'product' => $product,
            'relatedProducts' => $related,
        ]);
    }
}
