<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\ResearchGroups\Traits\HasAuditColumns;

/**
 * BudgetAllocation
 *
 * Represents an amount allocated from a FundingSource to a
 * ResearchGroup (and optionally scoped to a single ResearchProject).
 *
 * NOTE: Per the actual CREATE TABLE in SDD §4.2.6, this table has no
 * deleted_at/deleted_by columns — despite §4.7 listing it as
 * soft-delete enabled. Flagged for resolution at Migration level.
 *
 * SDD Reference: RGMS SDD §3.5.7, §4.2.6
 *
 * @property string $id
 * @property string $research_group_id
 * @property string|null $project_id
 * @property string $funding_source_id
 * @property float $allocated_amount
 * @property string $currency_code
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $updated_by
 */
final class BudgetAllocation extends Model
{
    use HasFactory;
    use HasUuids;
    use HasAuditColumns;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'budget_allocations';

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
        'research_group_id',
        'project_id',
        'funding_source_id',
        'allocated_amount',
        'currency_code',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * The research group this allocation belongs to.
     */
    public function researchGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchGroup::class, 'research_group_id');
    }

    /**
     * The project this allocation is scoped to (nullable —
     * group-level allocations have no project).
     */
    public function researchProject(): BelongsTo
    {
        return $this->belongsTo(ResearchProject::class, 'project_id');
    }

    /**
     * The funding source this allocation draws from.
     */
    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(FundingSource::class, 'funding_source_id');
    }

    /**
     * The expenditures recorded against this allocation.
     */
    public function budgetExpenditures(): HasMany
    {
        return $this->hasMany(BudgetExpenditure::class, 'allocation_id');
    }
}