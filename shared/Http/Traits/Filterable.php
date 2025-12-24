<?php

namespace Shared\Http\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filterable Trait
 * 
 * Provides advanced filtering, sorting, and search capabilities for Eloquent models
 */
trait Filterable
{
    /**
     * Apply filters to a query
     *
     * @param Builder $query
     * @param Request $request
     * @param array $allowedFilters Allowed filter fields
     * @return Builder
     */
    public function scopeFilter(Builder $query, Request $request, array $allowedFilters = []): Builder
    {
        // Apply field-specific filters
        foreach ($allowedFilters as $field) {
            if ($request->has($field)) {
                $value = $request->input($field);
                
                // Handle array values (IN clause)
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
        }

        return $query;
    }

    /**
     * Apply search to a query
     *
     * @param Builder $query
     * @param Request $request
     * @param array $searchableFields Fields to search in
     * @return Builder
     */
    public function scopeSearch(Builder $query, Request $request, array $searchableFields = []): Builder
    {
        if ($request->has('search') && !empty($searchableFields)) {
            $searchTerm = $request->input('search');
            
            $query->where(function ($q) use ($searchableFields, $searchTerm) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                }
            });
        }

        return $query;
    }

    /**
     * Apply sorting to a query
     *
     * @param Builder $query
     * @param Request $request
     * @param array $allowedSortFields Allowed sort fields
     * @param string $defaultSort Default sort field
     * @param string $defaultOrder Default sort order
     * @return Builder
     */
    public function scopeSort(
        Builder $query,
        Request $request,
        array $allowedSortFields = [],
        string $defaultSort = 'created_at',
        string $defaultOrder = 'desc'
    ): Builder {
        $sortBy = $request->input('sort_by', $defaultSort);
        $sortOrder = $request->input('sort_order', $defaultOrder);

        // Validate sort field
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = $defaultSort;
        }

        // Validate sort order
        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = $defaultOrder;
        }

        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Apply pagination to a query
     *
     * @param Builder $query
     * @param Request $request
     * @param int $defaultPerPage Default items per page
     * @param int $maxPerPage Maximum items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function scopePaginated(
        Builder $query,
        Request $request,
        int $defaultPerPage = 15,
        int $maxPerPage = 100
    ) {
        $perPage = min(
            (int) $request->input('per_page', $defaultPerPage),
            $maxPerPage
        );

        return $query->paginate($perPage);
    }

    /**
     * Apply date range filter
     *
     * @param Builder $query
     * @param Request $request
     * @param string $field Date field name
     * @return Builder
     */
    public function scopeDateRange(Builder $query, Request $request, string $field = 'created_at'): Builder
    {
        if ($request->has('date_from')) {
            $query->where($field, '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->where($field, '<=', $request->input('date_to'));
        }

        return $query;
    }

    /**
     * Apply all common filters, search, and sorting
     *
     * @param Builder $query
     * @param Request $request
     * @param array $config Configuration array
     * @return Builder
     */
    public function scopeApplyFilters(Builder $query, Request $request, array $config = []): Builder
    {
        $allowedFilters = $config['filters'] ?? [];
        $searchableFields = $config['searchable'] ?? [];
        $allowedSortFields = $config['sortable'] ?? [];
        $defaultSort = $config['default_sort'] ?? 'created_at';
        $defaultOrder = $config['default_order'] ?? 'desc';

        return $query
            ->filter($request, $allowedFilters)
            ->search($request, $searchableFields)
            ->sort($request, $allowedSortFields, $defaultSort, $defaultOrder);
    }
}
