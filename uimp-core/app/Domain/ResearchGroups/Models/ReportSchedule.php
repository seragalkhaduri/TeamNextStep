<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\ResearchGroups\Traits\HasAuditColumns;

/**
 * ReportSchedule
 *
 * Represents a recurring, scheduled report generation configuration
 * (SDD §3.12.7). Evaluated by the Laravel Scheduler to run
 * ReportService::runScheduled() when next_run_at is due.
 *
 * SDD Reference: RGMS SDD §3.12.3, §3.12.7
 *
 * @property string $id
 * @property string $report_type
 * @property string $format
 * @property string $frequency
 * @property array|null $scope_config
 * @property array $recipient_config
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $next_run_at
 */
final class ReportSchedule extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use HasAuditColumns;

    protected $table = 'report_schedules';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'report_type',
        'format',
        'frequency',
        'scope_config',
        'recipient_config',
        'is_active',
        'next_run_at',
    ];

    protected function casts(): array
    {
        return [
            'scope_config' => 'array',
            'recipient_config' => 'array',
            'is_active' => 'boolean',
            'next_run_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * The execution history entries generated from this schedule.
     */
    public function reportExecutionHistory(): HasMany
    {
        return $this->hasMany(ReportExecutionHistory::class, 'schedule_id');
    }
}