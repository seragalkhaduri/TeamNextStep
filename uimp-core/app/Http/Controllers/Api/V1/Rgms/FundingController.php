<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rgms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rgms\StoreExpenditureRequest;
use App\Http\Requests\Rgms\StoreFundingSourceRequest;
use App\Http\Resources\Rgms\BudgetSummaryResource;
use App\Http\Resources\Rgms\ExpenditureResource;
use App\Http\Resources\Rgms\FundingSourceResource;
use App\Domain\ResearchGroups\Models\FundingSource;
use App\Domain\ResearchGroups\Models\ResearchProject;
use App\Domain\ResearchGroups\Repositories\ExpenditureRepository;
use App\Domain\ResearchGroups\Repositories\FundingSourceRepository;
use App\Domain\ResearchGroups\Services\FundingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * FundingController
 *
 * Thin controller for the Funding Source and Budget Monitoring
 * module.
 *
 * SDD Reference: RGMS SDD §3.5.3, §3.5.4
 */
final class FundingController extends Controller
{
    public function __construct(
        private readonly FundingService $service,
        private readonly FundingSourceRepository $repository,
        private readonly ExpenditureRepository $expenditureRepository,
    ) {
    }

    /**
     * GET /api/v1/funding-sources
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FundingSource::class);

        $filters = $request->only(['status', 'research_group_id']);
        $perPage = (int) $request->integer('per_page', 15);

        $sources = $this->repository->paginateAll($filters, $perPage);

        return response()->json([
            'data' => FundingSourceResource::collection($sources),
            'meta' => [
                'total' => $sources->total(),
                'per_page' => $sources->perPage(),
                'current_page' => $sources->currentPage(),
                'last_page' => $sources->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/funding-sources
     */
    public function store(StoreFundingSourceRequest $request): JsonResponse
    {
        $fundingSource = $this->service->registerFundingSource($request->validated());

        return FundingSourceResource::make($fundingSource)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/funding-sources/{id}
     */
    public function show(FundingSource $funding_source): FundingSourceResource
    {
        $this->authorize('view', $funding_source);

        return FundingSourceResource::make($funding_source);
    }

    /**
     * PUT /api/v1/funding-sources/{id}
     */
    public function update(StoreFundingSourceRequest $request, FundingSource $funding_source): FundingSourceResource
    {
        $this->authorize('update', $funding_source);

        $updated = $this->service->updateFundingSource($funding_source, $request->validated());

        return FundingSourceResource::make($updated);
    }

    /**
     * POST /api/v1/projects/{pid}/expenditures
     */
    public function storeExpenditure(StoreExpenditureRequest $request, ResearchProject $project): JsonResponse
    {
        $fundingSource = FundingSource::query()->findOrFail($request->validated('funding_source_id'));

        $expenditure = $this->service->registerExpenditure($project, $fundingSource, $request->validated());

        return ExpenditureResource::make($expenditure)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/projects/{pid}/expenditures
     */
    public function listExpenditures(ResearchProject $project): JsonResponse
    {
        $this->authorize('viewProjectFinancials', $project);

        $expenditures = $this->expenditureRepository->findByProject($project->id);

        return response()->json([
            'data' => ExpenditureResource::collection($expenditures),
        ]);
    }

    /**
     * GET /api/v1/projects/{pid}/budget-summary
     */
    public function budgetSummary(ResearchProject $project): BudgetSummaryResource
    {
        $this->authorize('viewProjectFinancials', $project);

        return BudgetSummaryResource::make($this->service->computeBudgetSummary($project->id));
    }

    /**
     * GET /api/v1/funding-sources/dashboard
     */
    public function dashboard(): JsonResponse
    {
        $this->authorize('viewDashboard', FundingSource::class);

        return response()->json(['data' => $this->service->computeDashboard()]);
    }
}