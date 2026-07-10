<?php

namespace App\Domain\Students\Models;

use App\Domain\Audit\Traits\Auditable;
use App\Domain\Students\Enums\EnrollmentStatus;
use App\Domain\Students\Enums\Gender;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Normalizer;

/**
 * Student model (SDD §4.2).
 *
 * Dual-language name fields (name_en, name_ar) — both NOT NULL (DB-004).
 * Arabic text is NFC-normalized before persistence (SDD §4.5).
 */
class Student extends Model
{
    use HasUuids, SoftDeletes, Auditable;

    protected $fillable = [
        'institutional_id',
        'national_id',
        'name_en',
        'name_ar',
        'date_of_birth',
        'gender',
        'nationality',
        'email',
        'phone',
        'address',
        'enrollment_status',
        'admission_date',
        'graduation_date',
        'user_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'enrollment_status' => EnrollmentStatus::class,
            'date_of_birth' => 'date',
            'admission_date' => 'date',
            'graduation_date' => 'date',
        ];
    }

    protected static function getAuditEventType(): string
    {
        return 'DATA_CHANGE';
    }

    // ─── NFC Normalization for Arabic (SDD §4.5) ─────────────────

    protected static function booted(): void
    {
        static::saving(function (Student $student) {
            if ($student->isDirty('name_ar') && function_exists('normalizer_normalize')) {
                $student->name_ar = Normalizer::normalize($student->name_ar, Normalizer::FORM_C);
            }
        });
    }

    // ─── Relationships ───────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(\App\Domain\Auth\Models\User::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(\App\Domain\Auth\Models\User::class, 'created_by');
    }

    public function programs()
    {
        return $this->belongsToMany(
            \App\Domain\Organization\Models\Program::class,
            'student_programs'
        )->withPivot('enrollment_date');
    }
}
