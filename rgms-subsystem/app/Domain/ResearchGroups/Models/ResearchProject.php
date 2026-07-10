<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\ResearchGroups\Traits\HasAuditColumns;

/**
 * ResearchProject
 *
 * Represents a research project owned by a ResearchGroup, tracking
 * budget, schedule, risk status, and compliance status.
 *
 * SDD Reference: RGMS SDD §3.3.9, §4.2.3
 *
 * @property string $id
 * @property string $title
 * @property string $research_group_id
 * @property string $funding_agency
 * @property float $budget
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string $status
 * @property string $risk_status
 * @property string $compliance_status
 * @property string|null $risk_description
 * @property string|null $mitigation_actions
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $updated_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $deleted_by
 */
final class ResearchProject extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use HasAuditColumns;

    /**
     * Allowed status ENUM values (SDD §4.2.3).
     */
    public const STATUS_PLANNING = 'Planning';
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_ON_HOLD = 'On-Hold';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_TERMINATED = 'Terminated';

    /**
     * Allowed risk_status ENUM values (SDD §4.2.3).
     */
    public const RISK_LOW = 'Low';
    public const RISK_MEDIUM = 'Medium';
    public const RISK_HIGH = 'High';
    public const RISK_CRITICAL = 'Critical';

    /**
     * Allowed compliance_status ENUM values (SDD §4.2.3).
     */
    public const COMPLIANCE_COMPLIANT = 'Compliant';
    public const COMPLIANCE_UNDER_REVIEW = 'Under-Review';
    public const COMPLIANCE_NON_COMPLIANT = 'Non-Compliant';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'research_projects';

    /**
     * The primary key type (UUID via HasUuids trait).
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates the primary key is not auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'research_group_id',
        'funding_agency',
        'budget',
        'start_date',
        'end_date',
        'status',
        'risk_status',
        'compliance_status',
        'risk_description',
        'mitigation_actions',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * The research group that owns this project.
     */
    public function researchGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchGroup::class, 'research_group_id');
    }

    /**
     * The milestones defined for this project.
     */
    public function projectMilestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class, 'project_id');
    }

    /**
     * The contributors assigned to this project.
     */
    public function projectContributors(): HasMany
    {
        return $this->hasMany(ProjectContributor::class, 'project_id');
    }

    /**
     * The compliance records associated with this project.
     */
    public function complianceRecords(): HasMany
    {
        return $this->hasMany(ComplianceRecord::class, 'project_id');
    }

    /**
     * The budget allocation for this project.
     */
    public function budgetAllocation(): HasOne
    {
        return $this->hasOne(BudgetAllocation::class, 'project_id');
    }

    /**
     * Scope a query to filter projects by lifecycle status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter projects by risk status.
     */
    public function scopeByRisk(Builder $query, string $riskStatus): Builder
    {
        return $query->where('risk_status', $riskStatus);
    }

    /**
     * Scope a query to only include Active projects.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}