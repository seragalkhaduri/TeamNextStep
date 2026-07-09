<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected User $studentUser;
    protected User $auditorUser;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $studentRole = Role::create(['name' => 'STUDENT', 'guard_name' => 'web']);
        $auditorRole = Role::create(['name' => 'AUDITOR', 'guard_name' => 'web']);
        $adminRole = Role::create(['name' => 'SYSTEM_ADMIN', 'guard_name' => 'web']);

        // Create student user
        $this->studentUser = User::create([
            'username' => 'student',
            'email' => 'student@example.com',
            'password_hash' => Hash::make('password'),
        ]);
        $this->studentUser->assignRole($studentRole);

        // Create auditor user
        $this->auditorUser = User::create([
            'username' => 'auditor',
            'email' => 'auditor@example.com',
            'password_hash' => Hash::make('password'),
        ]);
        $this->auditorUser->assignRole($auditorRole);

        // Create admin user
        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password_hash' => Hash::make('password'),
        ]);
        $this->adminUser->assignRole($adminRole);
    }

    public function test_student_cannot_access_audit_logs(): void
    {
        $this->actingAs($this->studentUser, 'sanctum');

        $response = $this->getJson('/api/v1/audit/logs');

        $response->assertStatus(403);
    }

    public function test_auditor_can_access_audit_logs(): void
    {
        $this->actingAs($this->auditorUser, 'sanctum');

        $response = $this->getJson('/api/v1/audit/logs');

        $response->assertStatus(200);
    }

    public function test_auditor_cannot_create_reference_data(): void
    {
        $this->actingAs($this->auditorUser, 'sanctum');

        $response = $this->postJson('/api/v1/campuses', [
            'nameEn' => 'East Campus',
            'nameAr' => 'الحرم الشرقي',
        ]);

        $response->assertStatus(403);
    }

    public function test_system_admin_can_create_reference_data(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $response = $this->postJson('/api/v1/campuses', [
            'nameEn' => 'East Campus',
            'nameAr' => 'الحرم الشرقي',
            'address' => 'Tripoli',
        ]);

        $response->assertStatus(201);
    }
}
