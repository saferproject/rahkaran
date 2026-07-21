<?php

namespace App\Services;

use App\Models\ApiClient;
use App\Models\ApiRefreshToken;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class BackendTokenService
{
    /**
     * @return array{token_type: string, access_token: string, expires_in: int, refresh_token: string, refresh_expires_in: int}
     */
    public function issue(ApiClient $client): array
    {
        return DB::transaction(fn (): array => $this->createTokenPair($client));
    }

    /**
     * @return array{token_type: string, access_token: string, expires_in: int, refresh_token: string, refresh_expires_in: int}
     */
    public function refresh(string $plainRefreshToken): array
    {
        return DB::transaction(function () use ($plainRefreshToken): array {
            $refreshToken = ApiRefreshToken::query()
                ->with('apiClient')
                ->where('token_hash', hash('sha256', $plainRefreshToken))
                ->lockForUpdate()
                ->first();

            if (! $refreshToken?->isUsable()) {
                throw new AuthenticationException('The refresh token is invalid or expired.');
            }

            $refreshToken->update(['used_at' => now()]);

            if ($refreshToken->access_token_id !== null) {
                PersonalAccessToken::query()->whereKey($refreshToken->access_token_id)->delete();
            }

            return $this->createTokenPair($refreshToken->apiClient);
        });
    }

    public function revoke(ApiClient $client, ?string $plainRefreshToken, ?PersonalAccessToken $accessToken): void
    {
        DB::transaction(function () use ($client, $plainRefreshToken, $accessToken): void {
            if ($plainRefreshToken !== null) {
                $client->refreshTokens()
                    ->where('token_hash', hash('sha256', $plainRefreshToken))
                    ->whereNull('revoked_at')
                    ->update(['revoked_at' => now()]);
            }

            $accessToken?->delete();
        });
    }

    /**
     * @return array{token_type: string, access_token: string, expires_in: int, refresh_token: string, refresh_expires_in: int}
     */
    private function createTokenPair(ApiClient $client): array
    {
        $accessTtl = max(1, (int) config('backend-api.access_token_ttl'));
        $refreshTtl = max($accessTtl, (int) config('backend-api.refresh_token_ttl'));
        $accessExpiresAt = now()->addMinutes($accessTtl);
        $refreshExpiresAt = now()->addMinutes($refreshTtl);

        $accessToken = $client->createToken(
            name: 'backend-access-token',
            abilities: $client->abilities,
            expiresAt: $accessExpiresAt,
        );
        $plainRefreshToken = Str::random(80);

        $client->refreshTokens()->create([
            'token_hash' => hash('sha256', $plainRefreshToken),
            'access_token_id' => $accessToken->accessToken->getKey(),
            'expires_at' => $refreshExpiresAt,
        ]);

        $client->forceFill(['last_used_at' => now()])->save();

        return [
            'token_type' => 'Bearer',
            'access_token' => $accessToken->plainTextToken,
            'expires_in' => $accessTtl * 60,
            'refresh_token' => $plainRefreshToken,
            'refresh_expires_in' => $refreshTtl * 60,
        ];
    }
}
