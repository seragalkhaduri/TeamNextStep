<?php

namespace App\Domain\Facilities\Models;

use App\Domain\Audit\Traits\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use HasUuids, SoftDeletes, Auditable;

    protected $fillable = ['name_en', 'name_ar', 'code', 'campus_id'];

    protected static function getAuditEventType(): string
    {
        return 'DATA_CHANGE';
    }

    // ─── Relationships ───────────────────────────────────────────────

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
