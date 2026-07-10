<?php

namespace App\Providers;

use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Models\ResearchProject;
use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Models\Publication;
use App\Domain\ResearchGroups\Models\Patent;
use App\Domain\ResearchGroups\Models\ResearchEquipment;
use App\Domain\ResearchGroups\Models\EquipmentAssignment;
use App\Domain\ResearchGroups\Models\FundingSource;
use App\Domain\ResearchGroups\Models\ComplianceRecord;
use App\Domain\ResearchGroups\Models\ProjectMilestone;
use App\Domain\ResearchGroups\Models\ReportSchedule;
use App\Domain\ResearchGroups\Policies\ResearchGroupPolicy;
use App\Domain\ResearchGroups\Policies\ProjectPolicy;
use App\Domain\ResearchGroups\Policies\GroupMemberPolicy;
use App\Domain\ResearchGroups\Policies\PublicationPolicy;
use App\Domain\ResearchGroups\Policies\PatentPolicy;
use App\Domain\ResearchGroups\Policies\EquipmentPolicy;
use App\Domain\ResearchGroups\Policies\EquipmentAssignmentPolicy;
use App\Domain\ResearchGroups\Policies\FundingPolicy;
use App\Domain\ResearchGroups\Policies\CompliancePolicy;
use App\Domain\ResearchGroups\Policies\MilestonePolicy;
use App\Domain\ResearchGroups\Policies\ReportPolicy;
use App\Domain\ResearchGroups\Policies\AnalyticsPolicy;
use App\Domain\ResearchGroups\Policies\DashboardPolicy;
use App\Domain\ResearchGroups\Services\Clients\UimpMasterDataClient;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind UimpMasterDataClient as a singleton — it now performs
        // direct Eloquent queries instead of HTTP calls, but the
        // binding is kept so the constructor injection throughout
        // RGMS Services and Rules continues to work unchanged.
        $this->app->singleton(UimpMasterDataClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── RGMS Gate Policies ────────────────────────────────────────
        Gate::policy(ResearchGroup::class, ResearchGroupPolicy::class);
        Gate::policy(ResearchProject::class, ProjectPolicy::class);
        Gate::policy(GroupMembership::class, GroupMemberPolicy::class);
        Gate::policy(Publication::class, PublicationPolicy::class);
        Gate::policy(Patent::class, PatentPolicy::class);
        Gate::policy(ResearchEquipment::class, EquipmentPolicy::class);
        Gate::policy(EquipmentAssignment::class, EquipmentAssignmentPolicy::class);
        Gate::policy(FundingSource::class, FundingPolicy::class);
        Gate::policy(ComplianceRecord::class, CompliancePolicy::class);
        Gate::policy(ProjectMilestone::class, MilestonePolicy::class);
        Gate::policy(ReportSchedule::class, ReportPolicy::class);
    }
}
