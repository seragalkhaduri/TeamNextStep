<?php

namespace App\Domain\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * LoginResource — exact response shape from SDD §5.1:
 *
 * {
 *   accessToken, tokenType: "Bearer", expiresIn: 900,
 *   userId, roles: [...], preferredLanguage
 * }
 */
class LoginResource extends JsonResource
{
    private string $accessToken;

    public function __construct($user, string $accessToken)
    {
        parent::__construct($user);
        $this->accessToken = $accessToken;
    }

    public function toArray(Request $request): array
    {
        $expiresIn = (int) config('sanctum.expiration', 15) * 60; // Convert to seconds

        return [
            'accessToken' => $this->accessToken,
            'tokenType' => 'Bearer',
            'expiresIn' => $expiresIn,
            'userId' => $this->resource->id,
            'roles' => $this->resource->getRoleNames()->toArray(),
            'preferredLanguage' => $this->resource->preferred_language,
        ];
    }
}
