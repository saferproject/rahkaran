<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IssueBackendTokenRequest;
use App\Http\Requests\RefreshBackendTokenRequest;
use App\Http\Requests\RevokeBackendTokenRequest;
use App\Models\ApiClient;
use App\Services\BackendTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class BackendTokenController extends Controller
{
    public function __construct(
        private readonly BackendTokenService $tokenService,
    ) {}

    public function issue(IssueBackendTokenRequest $request): JsonResponse
    {
        $data = $request->validated();
        $client = ApiClient::query()
            ->where('client_id', $data['client_id'])
            ->where('is_active', true)
            ->first();

        if (! $client || ! Hash::check($data['client_secret'], $client->client_secret_hash)) {
            return response()->json([
                'message' => 'Invalid client credentials.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json($this->tokenService->issue($client));
    }

    public function refresh(RefreshBackendTokenRequest $request): JsonResponse
    {
        return response()->json(
            $this->tokenService->refresh($request->validated('refresh_token'))
        );
    }

    public function revoke(RevokeBackendTokenRequest $request): Response
    {
        $client = $request->user();

        abort_unless($client instanceof ApiClient, Response::HTTP_FORBIDDEN);

        $this->tokenService->revoke(
            client: $client,
            plainRefreshToken: $request->validated('refresh_token'),
            accessToken: $client->currentAccessToken(),
        );

        return response()->noContent();
    }
}
