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
 * BudgetExpenditure
 *
 * Immutable financial record. Never updated or deleted — application
 * DB user has UPDATE/DELETE privileges revoked at the database level
 * (SDD §4.2.7). Corrections are recorded as new reversal entries with
 * a negative amount referencing the original entry.
 *
 * Deliberately does NOT use SoftDeletes (SDD §3.5.5 design decision).
 *
 * SDD Reference: RGMS SDD §3.5.5, §4.2.7
 *
 * @property string $id
 * @property string $project_id
 * @property string $funding_source_id
 * @property string|null $allocation_id
 * @property string $category
 * @property float $amount
 * @property string $currency_code
 * @property \Illuminate\Support\Carbon $expenditure_date
 * @property string $description
 * @property string|null $reference_expenditure_id
 * @property string|null $override_authorized_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $created_by
 */
final class BudgetExpenditure extends Model
{
    use HasFactory;
    use HasUuids;
    use HasAuditColumns;

    /**
     * Allowed category ENUM values (SDD §4.2.7).
     */
    public const CATEGORY_PERSONNEL = 'Personnel';
    public const CATEGORY_EQUIPMENT = 'Equipment';
    public const CATEGORY_TRAVEL = 'Travel';
    public const CATEGORY_CONSUMABLES = 'Consumables';
    public const CATEGORY_OVERHEAD = 'Overhead';
    public const CATEGORY_OTHER = 'Other';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'budget_expenditures';

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
     * Table has no updated_at column — record is immutable.
     *
     * @var string|null
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'funding_source_id',
        'allocation_id',
        'category',
        'amount',
        'currency_code',
        'expenditure_date',
        'description',
        'reference_expenditure_id',
        'override_authorized_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expenditure_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    /**
     * The project this expenditure was recorded against.
     */
    public function researchProject(): BelongsTo
    {
        return $this->belongsTo(ResearchProject::class, 'project_id');
    }

    /**
     * The funding source this expenditure draws from.
     */
    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(FundingSource::class, 'funding_source_id');
    }

    /**
     * The budget allocation this expenditure is charged against.
     */
    public function budgetAllocation(): BelongsTo
    {
        return $this->belongsTo(BudgetAllocation::class, 'allocation_id');
    }

    /**
     * The original expenditure entry this record reverses
     * (populated only on reversal entries).
     */
    public function referenceExpenditure(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reference_expenditure_id');
    }

    /**
     * Reversal entries that reference this expenditure as their origin.
     */
    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reference_expenditure_id');
    }
}