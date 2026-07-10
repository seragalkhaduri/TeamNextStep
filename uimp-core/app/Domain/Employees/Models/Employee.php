<?php

namespace App\Domain\Employees\Models;

use App\Domain\Audit\Traits\Auditable;
use App\Domain\Employees\Enums\AcademicRank;
use App\Domain\Employees\Enums\StaffType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Normalizer;

/**
 * Employee model (SDD §4.2).
 *
 * Single-table discriminator: staff_type = ACADEMIC | NON_ACADEMIC (§3.2.2).
 * academic_rank is nullable — only relevant when staff_type = ACADEMIC.
 * Arabic text NFC-normalized before persistence (§4.5).
 */
class Employee extends Model
{
    use HasUuids, SoftDeletes, Auditable;

    protected $fillable = [
        'institutional_id',
        'staff_type',
        'name_en',
        'name_ar',
        'email',
        'phone',
        'address',
        'academic_rank',
        'hire_date',
        'status',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'staff_type' => StaffType::class,
            'academic_rank' => AcademicRank::class,
            'hire_date' => 'date',
        ];
    }

    protected static function getAuditEventType(): string
    {
        return 'DATA_CHANGE';
    }

    // ─── NFC Normalization for Arabic (SDD §4.5) ─────────────────

    protected static function booted(): void
    {
        static::saving(function (Employee $employee) {
            if ($employee->isDirty('name_ar') && function_exists('normalizer_normalize')) {
                $employee->name_ar = Normalizer::normalize($employee->name_ar, Normalizer::FORM_C);
            }
        });
    }

    // ─── Relationships ───────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(\App\Domain\Auth\Models\User::class);
    }

    public function departments()
    {
        return $this->belongsToMany(
            \App\Domain\Organization\Models\Department::class,
            'employee_departments'
        )->withPivot('assigned_at');
    }

    public function history()
    {
        return $this->hasMany(EmployeeHistory::class)->orderByDesc('changed_at');
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    public function isAcademic(): bool
    {
        return $this->staff_type === StaffType::ACADEMIC;
    }
}
