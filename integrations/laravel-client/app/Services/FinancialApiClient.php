<?php

namespace App\Services;

use App\Exceptions\FinancialApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class FinancialApiClient
{
    /**
     * Register a financial voucher.
     */
    public function registerVoucher(array $payload, ?string $idempotencyKey = null): mixed
    {
        return $this->post('/api/v1/financial/vouchers', $payload, $idempotencyKey);
    }

    /**
     * Register a detailed ledger (DL).
     */
    public function registerDL(array $payload, ?string $idempotencyKey = null): mixed
    {
        return $this->post('/api/v1/financial/dls', $payload, $idempotencyKey);
    }

    /**
     * Generate a party.
     */
    public function generateParty(array $payload, ?string $idempotencyKey = null): mixed
    {
        return $this->post('/api/v1/financial/parties', $payload, $idempotencyKey);
    }

    /**
     * Revoke the active access and refresh tokens.
     */
    public function revoke(): void
    {
        $tokens = $this->cachedTokens();

        if ($tokens === null) {
            return;
        }

        $response = $this->request()
            ->withToken($tokens['access_token'])
            ->post('/api/v1/backend-auth/revoke', [
                'refresh_token' => $tokens['refresh_token'],
            ]);

        $this->forgetTokens();

        if ($response->failed() && $response->status() !== 401) {
            throw FinancialApiException::fromResponse($response);
        }
    }

    private function post(string $path, array $payload, ?string $idempotencyKey): mixed
    {
        $idempotencyKey ??= (string) Str::uuid();
        $response = $this->sendAuthenticated($path, $payload, $idempotencyKey);

        if ($response->status() === 401) {
            $this->renewAccessToken(force: true);
            $response = $this->sendAuthenticated($path, $payload, $idempotencyKey);
        }

        if ($response->failed()) {
            throw FinancialApiException::fromResponse($response);
        }

        return $response->json();
    }

    private function sendAuthenticated(string $path, array $payload, string $idempotencyKey): Response
    {
        return $this->request()
            ->withToken($this->accessToken())
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->post($path, $payload);
    }

    private function accessToken(): string
    {
        $tokens = $this->cachedTokens();

        if ($tokens !== null && $tokens['access_expires_at'] > now()->addSeconds(30)->timestamp) {
            return $tokens['access_token'];
        }

        return $this->renewAccessToken();
    }

    private function renewAccessToken(bool $force = false): string
    {
        return Cache::lock($this->cacheKey().':lock', 15)->block(10, function () use ($force): string {
            $tokens = $this->cachedTokens();

            if (! $force && $tokens !== null && $tokens['access_expires_at'] > now()->addSeconds(30)->timestamp) {
                return $tokens['access_token'];
            }

            if ($tokens !== null && $tokens['refresh_expires_at'] > now()->addSeconds(30)->timestamp) {
                try {
                    return $this->storeTokens($this->refreshTokens($tokens['refresh_token']));
                } catch (FinancialApiException $exception) {
                    if ($exception->statusCode() !== 401) {
                        throw $exception;
                    }

                    $this->forgetTokens();
                }
            }

            return $this->storeTokens($this->issueTokens());
        });
    }

    private function issueTokens(): array
    {
        $response = $this->request()->post('/api/v1/backend-auth/token', [
            'client_id' => config('avanseyr-financial-api.client_id'),
            'client_secret' => config('avanseyr-financial-api.client_secret'),
        ]);

        return $this->validatedTokenResponse($response);
    }

    private function refreshTokens(string $refreshToken): array
    {
        $response = $this->request()->post('/api/v1/backend-auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        return $this->validatedTokenResponse($response);
    }

    private function validatedTokenResponse(Response $response): array
    {
        if ($response->failed()) {
            throw FinancialApiException::fromResponse($response);
        }

        $tokens = $response->json();

        if (! is_array($tokens)
            || ! is_string($tokens['access_token'] ?? null)
            || ! is_string($tokens['refresh_token'] ?? null)
            || ! is_numeric($tokens['expires_in'] ?? null)
            || ! is_numeric($tokens['refresh_expires_in'] ?? null)) {
            throw new FinancialApiException('Financial API returned an invalid token response.');
        }

        return $tokens;
    }

    private function storeTokens(array $tokens): string
    {
        $now = now();
        $cached = [
            'access_token' => $tokens['access_token'],
            'access_expires_at' => $now->timestamp + (int) $tokens['expires_in'],
            'refresh_token' => $tokens['refresh_token'],
            'refresh_expires_at' => $now->timestamp + (int) $tokens['refresh_expires_in'],
        ];
        $cacheSeconds = max(60, (int) $tokens['refresh_expires_in'] - 30);

        Cache::put(
            $this->cacheKey(),
            Crypt::encryptString(json_encode($cached, JSON_THROW_ON_ERROR)),
            $now->copy()->addSeconds($cacheSeconds),
        );

        return $cached['access_token'];
    }

    private function cachedTokens(): ?array
    {
        $encrypted = Cache::get($this->cacheKey());

        if (! is_string($encrypted)) {
            return null;
        }

        try {
            $tokens = json_decode(Crypt::decryptString($encrypted), true, flags: JSON_THROW_ON_ERROR);

            return is_array($tokens) ? $tokens : null;
        } catch (Throwable) {
            $this->forgetTokens();

            return null;
        }
    }

    private function forgetTokens(): void
    {
        Cache::forget($this->cacheKey());
    }

    private function cacheKey(): string
    {
        $configuredKey = config('avanseyr-financial-api.cache_key');

        if (is_string($configuredKey) && $configuredKey !== '') {
            return $configuredKey;
        }

        return 'avanseyr-financial-api:tokens:'.hash('sha256', implode('|', [
            (string) config('avanseyr-financial-api.base_url'),
            (string) config('avanseyr-financial-api.client_id'),
        ]));
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('avanseyr-financial-api.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout((int) config('avanseyr-financial-api.connect_timeout', 5))
            ->timeout((int) config('avanseyr-financial-api.timeout', 30))
            ->withOptions([
                'verify' => (bool) config('avanseyr-financial-api.verify_tls', true),
            ]);
    }
}
