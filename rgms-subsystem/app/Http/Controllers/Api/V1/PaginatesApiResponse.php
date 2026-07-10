<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Helper trait for consistent paginated API responses per SDD §7:
 * { page, size, totalElements, content: [...] }
 */
trait PaginatesApiResponse
{
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $resourceClass): JsonResponse
    {
        return response()->json([
            'page' => $paginator->currentPage(),
            'size' => $paginator->perPage(),
            'totalElements' => $paginator->total(),
            'content' => $resourceClass::collection($paginator->items()),
        ]);
    }
}
