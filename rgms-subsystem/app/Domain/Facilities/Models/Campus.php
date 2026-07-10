<?php

namespace App\Domain\Facilities\Models;

use App\Domain\Audit\Traits\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campus extends Model
{
    use HasUuids, SoftDeletes, Auditable;

    protected $fillable = ['name_en', 'name_ar', 'address'];

    protected static function getAuditEventType(): string
    {
        return 'DATA_CHANGE';
    }

    // ─── Relationships ───────────────────────────────────────────────

    public function buildings()
    {
        return $this->hasMany(Building::class);
    }

    public function rooms()
    {
        return $this->hasManyThrough(Room::class, Building::class);
    }
}
