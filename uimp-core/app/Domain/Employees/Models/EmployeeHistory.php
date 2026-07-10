<?php

namespace App\Domain\Employees\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * EmployeeHistory — append-only change log (FR-EMP-004).
 *
 * Records every field change on an employee record.
 * No updated_at, no soft deletes — insert-only.
 */
class EmployeeHistory extends Model
{
    use HasUuids;

    protected $table = 'employee_history';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'field_changed',
        'old_value',
        'new_value',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
