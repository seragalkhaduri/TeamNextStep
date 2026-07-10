<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthLockoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_locks_after_five_failed_attempts(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'email' => 'testuser@example.com',
            'password_hash' => Hash::make('correct_password'),
            'preferred_language' => 'ar',
            'is_active' => true,
        ]);

        // Fail 5 times (attempts 1 to 5)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'username' => 'testuser',
                'password' => 'wrong_password',
            ]);

            $response->assertStatus(401); // Each of the failed attempts returns 401
        }

        // The 5th failure should have locked the user
        $this->assertNotNull($user->fresh()->locked_until);
        $this->assertEquals(0, $user->fresh()->failed_login_count); // reset on lock

        // Trying with correct password (6th attempt) while locked should fail with 423 Locked
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'testuser',
            'password' => 'correct_password',
        ]);

        $response->assertStatus(423);
    }
}
