<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * ResearchGroupCollection
 *
 * Wraps a LengthAwarePaginator of ResearchGroup records with
 * pagination metadata (SDD §3.1.11).
 *
 * SDD Reference: RGMS SDD §3.1.11
 */
final class ResearchGroupCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ResearchGroupResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $this->resource;

        return [
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}