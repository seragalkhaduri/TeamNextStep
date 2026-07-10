<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auth\Requests\LoginRequest;
use App\Domain\Auth\Requests\PasswordResetConfirmRequest;
use App\Domain\Auth\Requests\PasswordResetRequestForm;
use App\Domain\Auth\Requests\RefreshTokenRequest;
use App\Domain\Auth\Requests\UpdateUserRolesRequest;
use App\Domain\Auth\Resources\LoginResource;
use App\Domain\Auth\Resources\UserResource;
use App\Domain\Auth\Services\AuthService;
use App\Domain\Auth\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AuthController — thin controller, delegates to AuthService (SDD §2 — Architecture Rule 2).
 *
 * Endpoints per SDD §5.1:
 *   POST /api/v1/auth/login
 *   POST /api/v1/auth/refresh
 *   POST /api/v1/auth/logout
 *   POST /api/v1/auth/password-reset/request
 *   POST /api/v1/auth/password-reset/confirm
 *   PUT  /api/v1/auth/roles/{userId}
 */
class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * POST /api/v1/auth/login
     *
     * Response: { accessToken, tokenType, expiresIn, userId, roles, preferredLanguage }
     * Refresh token set as HttpOnly cookie.
     * Errors: 401 invalid credentials, 423 account locked.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated('username'),
            $request->validated('password')
        );

        $resource = new LoginResource($result['user'], $result['accessToken']);

        // Set refresh token as HttpOnly cookie (SDD §5.1)
        $refreshCookie = cookie(
            'refresh_token',
            $result['refreshToken'],
            (int) config('uimp.refresh_token_days', 7) * 24 * 60, // minutes
            '/api/v1/auth/refresh',  // path — only sent to refresh endpoint
            null,                     // domain
            true,                     // secure (HTTPS only)
            true,                     // httpOnly
            false,                    // raw
            'Strict'                  // sameSite
        );

        return response()
            ->json($resource)
            ->withCookie($refreshCookie);
    }

    /**
     * POST /api/v1/auth/refresh
     *
     * Accepts refresh token from cookie or request body.
     * Returns new access token + rotated refresh token.
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        // Try cookie first, then request body
        $refreshToken = $request->cookie('refresh_token')
            ?? $request->validated('refresh_token');

        $result = $this->authService->refresh($refreshToken);

        $refreshCookie = cookie(
            'refresh_token',
            $result['refreshToken'],
            (int) config('uimp.refresh_token_days', 7) * 24 * 60,
            '/api/v1/auth/refresh',
            null,
            true,
            true,
            false,
            'Strict'
        );

        return response()->json([
            'accessToken' => $result['accessToken'],
            'tokenType' => 'Bearer',
            'expiresIn' => (int) config('sanctum.expiration', 15) * 60,
        ])->withCookie($refreshCookie);
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Revokes all tokens (access + refresh).
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        // Clear the refresh token cookie
        $clearCookie = cookie()->forget('refresh_token');

        return response()
            ->json(['message' => 'Logged out successfully.'])
            ->withCookie($clearCookie);
    }

    /**
     * POST /api/v1/auth/password-reset/request
     *
     * Always returns 200 (don't reveal whether email exists).
     */
    public function requestPasswordReset(PasswordResetRequestForm $request): JsonResponse
    {
        $this->authService->requestPasswordReset(
            $request->validated('email')
        );

        // Always return success — don't reveal if email exists (security best practice)
        return response()->json([
            'message' => 'If the email exists, a password reset link will be sent.',
        ]);
    }

    /**
     * POST /api/v1/auth/password-reset/confirm
     *
     * Resets password using the token.
     */
    public function confirmPasswordReset(PasswordResetConfirmRequest $request): JsonResponse
    {
        $this->authService->resetPassword(
            $request->validated('token'),
            $request->validated('password')
        );

        return response()->json([
            'message' => 'Password has been reset successfully.',
        ]);
    }

    /**
     * PUT /api/v1/auth/roles/{userId}
     *
     * Body: { roleIds: [...] }
     * Admin-only. Returns updated roles.
     */
    public function updateRoles(UpdateUserRolesRequest $request, string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $updatedUser = $this->authService->updateUserRoles(
            $user,
            $request->validated('roleIds')
        );

        return response()->json([
            'userId' => $updatedUser->id,
            'roles' => $updatedUser->roles->pluck('name')->toArray(),
        ]);
    }
}
