<?php

namespace Tests\Feature;

use App\Domain\Audit\Models\AuditLog;
use Tests\TestCase;

class AuditImmutabilityTest extends TestCase
{
    public function test_audit_logs_are_strictly_immutable_in_application(): void
    {
        $log = new AuditLog([
            'event_type' => 'DATA_CHANGE',
            'entity_type' => 'Student',
            'action' => 'CREATE',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AuditLog records are immutable. UPDATE operations are forbidden');

        $log->update(['action' => 'UPDATE']);
    }

    public function test_audit_logs_cannot_be_deleted_in_application(): void
    {
        $log = new AuditLog([
            'event_type' => 'DATA_CHANGE',
            'entity_type' => 'Student',
            'action' => 'CREATE',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AuditLog records are immutable. DELETE operations are forbidden');

        $log->delete();
    }
}
