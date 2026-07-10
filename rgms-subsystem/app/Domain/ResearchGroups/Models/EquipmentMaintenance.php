<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\ResearchGroups\Traits\HasAuditColumns;

/**
 * EquipmentMaintenance
 *
 * Represents a single preventive or corrective maintenance log entry
 * for a ResearchEquipment asset. No soft delete — maintenance history
 * is a permanent log (SDD §4.2.14).
 *
 * SDD Reference: RGMS SDD §3.8.7, §4.2.14
 *
 * @property string $id
 * @property string $equipment_id
 * @property string $maintenance_type
 * @property \Illuminate\Support\Carbon $scheduled_date
 * @property \Illuminate\Support\Carbon|null $completion_date
 * @property string $performed_by
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $created_by
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $updated_by
 */
final class EquipmentMaintenance extends Model
{
    use HasFactory;
    use HasUuids;
    use HasAuditColumns;

    /**
     * Allowed maintenance_type ENUM values (SDD §4.2.14).
     */
    public const TYPE_PREVENTIVE = 'Preventive';
    public const TYPE_CORRECTIVE = 'Corrective';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'equipment_maintenance';

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
        'equipment_id',
        'maintenance_type',
        'scheduled_date',
        'completion_date',
        'performed_by',
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
            'scheduled_date' => 'date',
            'completion_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * The equipment asset this maintenance record belongs to.
     */
    public function researchEquipment(): BelongsTo
    {
        return $this->belongsTo(ResearchEquipment::class, 'equipment_id');
    }
}