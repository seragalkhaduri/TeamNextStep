<?php

namespace Tests\Feature;

use App\Domain\Subsystems\Enums\SubsystemStatus;
use App\Domain\Subsystems\Models\Subsystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SubsystemAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register a temporary test route protected by subsystem.auth middleware
        Route::middleware('subsystem.auth')
            ->get('/_test/subsystem-secure', function () {
                $subsystem = request()->attributes->get('authenticated_subsystem');
                return response()->json([
                    'message' => 'success',
                    'subsystemId' => $subsystem->id
                ]);
            });
    }

    public function test_subsystem_authentication_fails_without_header(): void
    {
        $response = $this->getJson('/_test/subsystem-secure');

        $response->assertStatus(401);
        $response->assertJsonFragment(['message' => 'API Key required.']);
    }

    public function test_subsystem_authentication_fails_with_invalid_key(): void
    {
        $response = $this->getJson('/_test/subsystem-secure', [
            'X-API-Key' => 'invalid_key_123',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['message' => 'Invalid or suspended API Key.']);
    }

    public function test_subsystem_authentication_succeeds_with_valid_key(): void
    {
        $plainKey = 'uimp_test_key_abc123';
        $hash = hash('sha256', $plainKey);

        $subsystem = Subsystem::create([
            'name_en' => 'Test Subsystem',
            'name_ar' => 'النظام الفرعي التجريبي',
            'api_key_hash' => $hash,
            'status' => SubsystemStatus::ACTIVE->value,
            'contact_email' => 'test-sub@example.com',
        ]);

        $response = $this->getJson('/_test/subsystem-secure', [
            'X-API-Key' => $plainKey,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => 'success',
            'subsystemId' => $subsystem->id
        ]);
    }
}
