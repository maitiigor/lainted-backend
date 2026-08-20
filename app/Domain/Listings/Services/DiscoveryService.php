<?php

namespace App\Domain\Listings\Services;

use App\Domain\Listings\Models\Category;
use App\Domain\Listings\Models\Listing;
use App\Domain\Users\Models\User;
use App\Support\Enums\ConditionGrade;
use App\Support\Enums\ListingSource;
use App\Support\Enums\ListingStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DiscoveryService
{
    /**
     * Filter/sort surface for the public feed - deliberately its own
     * service rather than built inline in the controller (the pattern
     * used by ListingController::index/BundleController::index) because
     * this many filter dimensions plus sort branching plus category-
     * subtree expansion would make for an unreadable controller method.
     */
    public function feed(User $buyer, array $filters): LengthAwarePaginator
    {
        $query = Listing::query()
            ->where('status', ListingStatus::Active)
            ->with(['seller', 'photos']);

        $keyword = $this->keyword($filters);

        if ($keyword !== null) {
            $query->selectRaw('listings.*, MATCH(title, description) AGAINST (? IN BOOLEAN MODE) as relevance', [$keyword])
                ->whereFullText(['title', 'description'], $keyword, ['mode' => 'boolean']);
        }

        $this->applyCategory($query, $filters);
        $this->applyFilters($query, $buyer, $filters);
        $this->applySort($query, $buyer, $filters, $keyword !== null);

        return $query->paginate(20);
    }

    /**
     * Boolean-mode FULLTEXT with a trailing wildcard per token - MySQL's
     * default parser has no stemming, so "shirt" won't match "shirts" in
     * natural-language mode. Prefix matching sidesteps that gap.
     */
    private function keyword(array $filters): ?string
    {
        $q = trim((string) ($filters['q'] ?? ''));

        if ($q === '') {
            return null;
        }

        $tokens = preg_split('/\s+/', $q);

        return implode(' ', array_map(fn (string $token) => '+'.$token.'*', $tokens));
    }

    private function applyCategory(Builder $query, array $filters): void
    {
        if (empty($filters['category_id'])) {
            return;
        }

        $category = Category::with('children')->find($filters['category_id']);

        if ($category === null) {
            return;
        }

        $ids = $category->children->isEmpty()
            ? [$category->id]
            : [$category->id, ...$category->children->pluck('id')];

        $query->whereIn('category_id', $ids);
    }

    private function applyFilters(Builder $query, User $buyer, array $filters): void
    {
        if (! empty($filters['item_type'])) {
            $query->where('item_type', $filters['item_type']);
        }

        if (! empty($filters['size'])) {
            $query->where('size', $filters['size']);
        }

        if (! empty($filters['brand'])) {
            $query->where('brand', 'like', '%'.$filters['brand'].'%');
        }

        if (! empty($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }

        if (! empty($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        $minGrade = ConditionGrade::tryFrom($filters['min_grade'] ?? '');

        if ($minGrade !== null) {
            $query->whereIn('condition_grade', array_map(fn (ConditionGrade $g) => $g->value, $minGrade->atLeast()));
        }

        // "Near You" is a hard filter (doc: "items within buyer's LGA"),
        // distinct from the default sort's soft same-LGA-first tiering.
        // A buyer with no home LGA yet gets no filter applied, not an
        // error or an empty result set.
        if (! empty($filters['near_you']) && $buyer->lga_id !== null) {
            $query->where('lga_id', $buyer->lga_id);
        } elseif (! empty($filters['lga_id'])) {
            $query->where('lga_id', $filters['lga_id']);
        }

        $sellerType = ListingSource::tryFrom($filters['seller_type'] ?? '');

        if ($sellerType !== null) {
            $query->where('listing_source', $sellerType);
        }

        if (! empty($filters['is_wardrobe_cleanout'])) {
            $query->where('is_wardrobe_cleanout', true);
        }
    }

    private function applySort(Builder $query, User $buyer, array $filters, bool $hasKeyword): void
    {
        $sort = $filters['sort'] ?? null;

        match ($sort) {
            'newest' => $query->orderByDesc('published_at'),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'popular' => $query->orderByDesc('views_count'),
            default => $this->applyDefaultSort($query, $buyer, $hasKeyword),
        };
    }

    private function applyDefaultSort(Builder $query, User $buyer, bool $hasKeyword): void
    {
        if ($hasKeyword) {
            $query->orderByDesc('relevance');

            return;
        }

        if ($buyer->lga_id !== null) {
            $query->orderByRaw('lga_id = ? desc', [$buyer->lga_id]);
        }

        $query->orderByDesc('published_at');
    }
}
