<?php

namespace App\Domain\Facilities\Models;

use App\Domain\Audit\Traits\Auditable;
use App\Domain\Facilities\Enums\AvailabilityStatus;
use App\Domain\Facilities\Enums\RoomType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasUuids, SoftDeletes, Auditable;

    protected $fillable = [
        'name', 'code', 'room_type', 'capacity',
        'availability_status', 'building_id',
    ];

    protected function casts(): array
    {
        return [
            'room_type' => RoomType::class,
            'availability_status' => AvailabilityStatus::class,
            'capacity' => 'integer',
        ];
    }

    protected static function getAuditEventType(): string
    {
        return 'DATA_CHANGE';
    }

    // ─── Relationships ───────────────────────────────────────────────

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function campus()
    {
        return $this->hasOneThrough(
            Campus::class,
            Building::class,
            'id',
            'id',
            'building_id',
            'campus_id'
        );
    }
}
