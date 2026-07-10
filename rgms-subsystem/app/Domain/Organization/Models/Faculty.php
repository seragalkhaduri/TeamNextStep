<?php

namespace App\Domain\Organization\Models;

use App\Domain\Audit\Traits\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends Model
{
    use HasUuids, SoftDeletes, Auditable;

    protected $fillable = ['name_en', 'name_ar', 'code'];

    protected static function getAuditEventType(): string
    {
        return 'DATA_CHANGE';
    }

    // ─── Relationships ───────────────────────────────────────────────

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function programs()
    {
        return $this->hasManyThrough(Program::class, Department::class);
    }
}
