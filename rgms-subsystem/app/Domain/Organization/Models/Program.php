<?php

namespace App\Domain\Organization\Models;

use App\Domain\Audit\Traits\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasUuids, SoftDeletes, Auditable;

    protected $fillable = ['name_en', 'name_ar', 'degree_level', 'department_id'];

    protected static function getAuditEventType(): string
    {
        return 'DATA_CHANGE';
    }

    // ─── Relationships ───────────────────────────────────────────────

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function faculty()
    {
        return $this->hasOneThrough(
            Faculty::class,
            Department::class,
            'id',           // departments.id
            'id',           // faculties.id
            'department_id', // programs.department_id
            'faculty_id'     // departments.faculty_id
        );
    }

    public function students()
    {
        return $this->belongsToMany(
            \App\Domain\Students\Models\Student::class,
            'student_programs'
        )->withPivot('enrollment_date');
    }
}
