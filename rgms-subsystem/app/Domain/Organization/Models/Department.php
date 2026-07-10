<?php

namespace App\Domain\Organization\Models;

use App\Domain\Audit\Traits\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasUuids, SoftDeletes, Auditable;

    protected $fillable = [
        'name_en', 'name_ar', 'code',
        'faculty_id', 'parent_department_id',
    ];

    protected static function getAuditEventType(): string
    {
        return 'DATA_CHANGE';
    }

    // ─── Relationships ───────────────────────────────────────────────

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    /** Self-referential hierarchy (FR-ORG-003) */
    public function parentDepartment()
    {
        return $this->belongsTo(Department::class, 'parent_department_id');
    }

    public function childDepartments()
    {
        return $this->hasMany(Department::class, 'parent_department_id');
    }

    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    public function employees()
    {
        return $this->belongsToMany(
            \App\Domain\Employees\Models\Employee::class,
            'employee_departments'
        )->withPivot('assigned_at');
    }
}
