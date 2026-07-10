<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\ResearchGroups\Traits\HasAuditColumns;

/**
 * ResearchEquipment
 *
 * Represents a laboratory equipment asset owned by a ResearchGroup,
 * tracking acquisition cost, replacement value, useful life, and
 * current availability status.
 *
 * SDD Reference: RGMS SDD §3.8.8, §4.2.10
 *
 * @property string $id
 * @property string $research_group_id
 * @property string $asset_name
 * @property string $category
 * @property string $manufacturer
 * @property string $model_number
 * @property string $serial_number
 * @property \Illuminate\Support\Carbon $purchase_date
 * @property float $acquisition_cost
 * @property float|null $replacement_value
 * @property int|null $estimated_useful_life_years
 * @property string $laboratory_ref_id
 * @property string $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $updated_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $deleted_by
 */
final class ResearchEquipment extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use HasAuditColumns;

    /**
     * Allowed status ENUM values (SDD §4.2.10).
     */
    public const STATUS_AVAILABLE = 'Available';
    public const STATUS_BOOKED = 'Booked';
    public const STATUS_UNDER_MAINTENANCE = 'Under-Maintenance';
    public const STATUS_DECOMMISSIONED = 'Decommissioned';
    public const STATUS_IN_TRANSIT = 'In-Transit';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'research_equipment';

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
        'asset_name',
        'category',
        'manufacturer',
        'model_number',
        'serial_number',
        'purchase_date',
        'acquisition_cost',
        'replacement_value',
        'estimated_useful_life_years',
        'laboratory_ref_id',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'acquisition_cost' => 'decimal:2',
            'replacement_value' => 'decimal:2',
            'estimated_useful_life_years' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * The research group that owns this equipment.
     */
    public function researchGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchGroup::class, 'research_group_id');
    }

    /**
     * The maintenance history for this equipment.
     */
    public function equipmentMaintenance(): HasMany
    {
        return $this->hasMany(EquipmentMaintenance::class, 'equipment_id');
    }

    /**
     * The booking/assignment records for this equipment.
     */
    public function equipmentAssignments(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class, 'equipment_id');
    }

    /**
     * Scope a query to filter equipment by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter equipment by laboratory reference.
     */
    public function scopeByLaboratory(Builder $query, string $laboratoryRefId): Builder
    {
        return $query->where('laboratory_ref_id', $laboratoryRefId);
    }

    /**
     * Scope a query to only include Available equipment.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }
}