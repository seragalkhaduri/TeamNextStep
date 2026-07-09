<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rgms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rgms\ProductivityQueryRequest;
use App\Http\Resources\Rgms\ComparisonResource;
use App\Http\Resources\Rgms\ProductivityResource;
use App\Http\Resources\Rgms\TrendResource;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Services\AnalyticsService;

/**
 * AnalyticsController
 *
 * Thin controller for the Research Productivity Analytics module —
 * entirely read-only, no writes to any RGMS table.
 *
 * SDD Reference: RGMS SDD §3.11.3, §3.11.4
 */
final class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $service,
    ) {
    }

    /**
     * GET /api/v1/analytics/productivity
     */
    public function productivity(ProductivityQueryRequest $request): ProductivityResource
    {
        return ProductivityResource::make($this->service->computeProductivity($request->validated()));
    }

    /**
     * GET /api/v1/analytics/trends
     */
    public function trends(ProductivityQueryRequest $request): TrendResource
    {
        return TrendResource::make($this->service->computeTrends($request->validated()));
    }

    /**
     * GET /api/v1/analytics/comparisons
     */
    public function comparisons(ProductivityQueryRequest $request): ComparisonResource
    {
        return ComparisonResource::make($this->service->computeComparisons($request->validated()));
    }

    /**
     * GET /api/v1/analytics/research-groups/{gid}
     */
    public function groupStats(ResearchGroup $research_group): ProductivityResource
    {
        $this->authorize('viewGroupStats', $research_group);

        return ProductivityResource::make($this->service->getGroupStats($research_group->id));
    }
}