<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BackendTokenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_credentials_issue_access_and_refresh_tokens(): void
    {
        $client = $this->createClient();

        $response = $this->postJson('/api/v1/backend-auth/token', [
            'client_id' => $client->client_id,
            'client_secret' => 'client-secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure([
                'access_token',
                'expires_in',
                'refresh_token',
                'refresh_expires_in',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseCount('api_refresh_tokens', 1);
        $this->assertDatabaseMissing('api_refresh_tokens', [
            'token_hash' => $response->json('refresh_token'),
        ]);
    }

    public function test_invalid_client_credentials_are_rejected(): void
    {
        $client = $this->createClient();

        $this->postJson('/api/v1/backend-auth/token', [
            'client_id' => $client->client_id,
            'client_secret' => 'wrong-secret',
        ])->assertUnauthorized();
    }

    public function test_refresh_token_is_rotated_and_previous_access_is_revoked(): void
    {
        $client = $this->createClient();
        $issued = $this->issueTokens($client);

        $refreshed = $this->postJson('/api/v1/backend-auth/refresh', [
            'refresh_token' => $issued['refresh_token'],
        ])->assertOk();

        $this->assertNotSame($issued['access_token'], $refreshed->json('access_token'));
        $this->assertNotSame($issued['refresh_token'], $refreshed->json('refresh_token'));

        $this->withToken($issued['access_token'])
            ->getJson('/api/user')
            ->assertUnauthorized();

        $this->withToken($refreshed->json('access_token'))
            ->getJson('/api/user')
            ->assertOk();

        $this->postJson('/api/v1/backend-auth/refresh', [
            'refresh_token' => $issued['refresh_token'],
        ])->assertUnauthorized();
    }

    public function test_access_token_cannot_use_an_ungranted_ability(): void
    {
        $client = $this->createClient(['parties:create']);
        $token = $client->createToken('limited', ['parties:create'])->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/financial/dls', [])
            ->assertForbidden();
    }

    private function createClient(?array $abilities = null): ApiClient
    {
        return ApiClient::query()->create([
            'name' => 'Accounting backend',
            'client_id' => fake()->uuid(),
            'client_secret_hash' => Hash::make('client-secret'),
            'abilities' => $abilities ?? config('backend-api.default_abilities'),
        ]);
    }

    private function issueTokens(ApiClient $client): array
    {
        return $this->postJson('/api/v1/backend-auth/token', [
            'client_id' => $client->client_id,
            'client_secret' => 'client-secret',
        ])->assertOk()->json();
    }
}
