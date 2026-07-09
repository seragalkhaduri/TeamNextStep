<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReportExecutionHistory
 *
 * Permanent record of a single report generation execution — either
 * ad-hoc (schedule_id null) or triggered by a ReportSchedule.
 * No SoftDeletes (SDD §3.12.7: "permanent record").
 *
 * SDD Reference: RGMS SDD §3.12.3, §3.12.7
 *
 * @property string $id
 * @property string|null $schedule_id
 * @property string $report_type
 * @property string $format
 * @property array|null $scope_config
 * @property string|null $file_path
 * @property int|null $file_size
 * @property string $status
 * @property string $generated_by
 * @property \Illuminate\Support\Carbon $generated_at
 */
final class ReportExecutionHistory extends Model
{
    use HasFactory;
    use HasUuids;

    public const STATUS_QUEUED = 'Queued';
    public const STATUS_READY = 'Ready';
    public const STATUS_FAILED = 'Failed';
    public const STATUS_EXPIRED = 'Expired';

    protected $table = 'report_execution_history';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * This table has no created_at column (only generated_at) —
     * Eloquent's timestamp management is disabled for created_at;
     * updated_at is still tracked via the migration's own column.
     *
     * @var bool
     */
    const CREATED_AT = null;

    protected $fillable = [
        'schedule_id',
        'report_type',
        'format',
        'scope_config',
        'file_path',
        'file_size',
        'status',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'scope_config' => 'array',
            'file_size' => 'integer',
            'generated_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * The schedule that triggered this execution, if any
     * (null for ad-hoc, on-demand report generation).
     */
    public function reportSchedule(): BelongsTo
    {
        return $this->belongsTo(ReportSchedule::class, 'schedule_id');
    }
}