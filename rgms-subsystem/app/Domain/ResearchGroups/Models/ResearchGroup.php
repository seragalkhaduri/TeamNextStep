<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\ResearchGroups\Traits\HasAuditColumns;

/**
 * ResearchGroup
 *
 * Represents a research group — the primary organizational unit of RGMS.
 * Central aggregate root: owns memberships, projects, funding sources,
 * and status transition history.
 *
 * SDD Reference: RGMS SDD §3.1.9, §4.2.1
 *
 * @property string $id
 * @property string $group_name
 * @property string $research_field
 * @property string $research_area
 * @property string $status
 * @property string $pi_staff_id
 * @property string|null $department_ref_id
 * @property string|null $funding_source_id
 * @property float|null $budget_allocation
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $updated_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $deleted_by
 */
final class ResearchGroup extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use HasAuditColumns;

    /**
     * Allowed lifecycle status values (SDD §4.2.1 ENUM definition).
     */
    public const STATUS_DRAFT = 'Draft';
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_SUSPENDED = 'Suspended';
    public const STATUS_ARCHIVED = 'Archived';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'research_groups';

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
        'group_name',
        'research_field',
        'research_area',
        'status',
        'pi_staff_id',
        'department_ref_id',
        'budget_allocation',
        'funding_source_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget_allocation' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * The memberships allocated to this research group.
     */
    public function groupMemberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class, 'group_id');
    }

    /**
     * The research projects owned by this research group.
     */
    public function researchProjects(): HasMany
    {
        return $this->hasMany(ResearchProject::class, 'research_group_id');
    }

    /**
     * The funding sources associated with this research group.
     */
    public function fundingSources(): HasMany
    {
        return $this->hasMany(FundingSource::class, 'research_group_id');
    }

    /**
     * The full status transition history of this research group.
     */
    public function groupStatusHistory(): HasMany
    {
        return $this->hasMany(GroupStatusHistory::class, 'group_id');
    }

    /**
     * Scope a query to only include Active research groups.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope a query to filter research groups by research field.
     */
    public function scopeByField(Builder $query, string $field): Builder
    {
        return $query->where('research_field', $field);
    }

    /**
     * Scope a query to filter research groups by lifecycle status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}