<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use App\Domain\Students\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentDedupTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_create_duplicate_student_record(): void
    {
        // Create user with permission to register students
        $user = User::create([
            'username' => 'registrar_user',
            'email' => 'registrar@example.com',
            'password_hash' => Hash::make('password'),
            'preferred_language' => 'ar',
            'is_active' => true,
        ]);
        
        $role = \Spatie\Permission\Models\Role::create(['name' => 'REGISTRAR_STAFF', 'guard_name' => 'web']);
        $user->assignRole($role);

        // Sanity check
        $this->actingAs($user, 'sanctum');

        $studentData = [
            'institutionalId' => 'STU-2024-999',
            'nationalId' => 'LY-99999999',
            'nameEn' => 'John Doe',
            'nameAr' => 'جون دو',
            'dateOfBirth' => '2000-01-01',
            'gender' => 'MALE',
            'nationality' => 'Libyan',
            'email' => 'john.doe@example.com',
            'phone' => '+218-91-0000000',
            'address' => 'Tripoli',
            'admissionDate' => '2024-09-01',
        ];

        // Create first student successfully
        $response1 = $this->postJson('/api/v1/students', $studentData);
        $response1->assertStatus(201);
        $response1->assertHeader('Location');

        // Attempt creating identical record -> returns 409 conflict
        $response2 = $this->postJson('/api/v1/students', $studentData);
        $response2->assertStatus(409); // Conflict
        $response2->assertJsonFragment([
            'message' => 'A student with this national ID and institutional ID already exists (FR-STU-002).'
        ]);
    }
}
