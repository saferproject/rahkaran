<?php

namespace Tests\Feature;

use Avanseyr\FinancialApiClient\Data\GeneratePartyData;
use Avanseyr\FinancialApiClient\Data\PartyAddressData;
use Avanseyr\FinancialApiClient\Data\RegisterDLData;
use Avanseyr\FinancialApiClient\Data\RegisterVoucherData;
use Avanseyr\FinancialApiClient\Data\VoucherItemData;
use Avanseyr\FinancialApiClient\Enums\PartyGender;
use Avanseyr\FinancialApiClient\FinancialApiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

require_once __DIR__.'/../../packages/avanseyr-financial-api-client/src/Contracts/PayloadData.php';
require_once __DIR__.'/../../packages/avanseyr-financial-api-client/src/Enums/PartyGender.php';
require_once __DIR__.'/../../packages/avanseyr-financial-api-client/src/Enums/VoucherState.php';
require_once __DIR__.'/../../packages/avanseyr-financial-api-client/src/Data/PartyAddressData.php';
require_once __DIR__.'/../../packages/avanseyr-financial-api-client/src/Data/GeneratePartyData.php';
require_once __DIR__.'/../../packages/avanseyr-financial-api-client/src/Data/RegisterDLData.php';
require_once __DIR__.'/../../packages/avanseyr-financial-api-client/src/Data/VoucherItemData.php';
require_once __DIR__.'/../../packages/avanseyr-financial-api-client/src/Data/RegisterVoucherData.php';
require_once __DIR__.'/../../packages/avanseyr-financial-api-client/src/Exceptions/FinancialApiException.php';
require_once __DIR__.'/../../packages/avanseyr-financial-api-client/src/FinancialApiClient.php';

class FinancialApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'avanseyr-financial-api.base_url' => 'https://financial.test',
            'avanseyr-financial-api.client_id' => 'client-id',
            'avanseyr-financial-api.client_secret' => 'client-secret',
            'avanseyr-financial-api.cache_key' => 'financial-client-test-token',
        ]);

        Cache::flush();
    }

    public function test_it_obtains_and_reuses_a_cached_access_token(): void
    {
        Http::fake([
            'financial.test/api/v1/backend-auth/token' => Http::response($this->tokenPair('access-1', 'refresh-1')),
            'financial.test/api/v1/financial/dls' => Http::response(['ID' => 42]),
        ]);

        $client = app(FinancialApiClient::class);

        $this->assertSame(['ID' => 42], $client->registerDL(new RegisterDLData(
            Code: '1001',
            DLTypeRef: 2,
            Title: 'Customer 123',
            Description: 'Customer account',
            ReferenceID: 123,
        )));
        $this->assertSame(['ID' => 42], $client->registerDL($this->dlPayload()));

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://financial.test/api/v1/backend-auth/token'
            && $request['client_id'] === 'client-id'
            && $request['client_secret'] === 'client-secret');
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://financial.test/api/v1/financial/dls'
            && $request->hasHeader('Authorization', 'Bearer access-1')
            && $request->hasHeader('Idempotency-Key'));
    }

    public function test_it_refreshes_and_rotates_tokens_automatically(): void
    {
        Http::fake([
            'financial.test/api/v1/backend-auth/token' => Http::response($this->tokenPair('access-1', 'refresh-1', 1)),
            'financial.test/api/v1/backend-auth/refresh' => Http::response($this->tokenPair('access-2', 'refresh-2')),
            'financial.test/api/v1/financial/dls' => Http::response(['ID' => 42]),
        ]);

        $client = app(FinancialApiClient::class);
        $client->registerDL($this->dlPayload());
        $client->registerDL($this->dlPayload());

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://financial.test/api/v1/backend-auth/refresh'
            && $request['refresh_token'] === 'refresh-1');
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://financial.test/api/v1/financial/dls'
            && $request->hasHeader('Authorization', 'Bearer access-2'));
    }

    public function test_dtos_generate_the_complete_api_payload_shape(): void
    {
        $party = new GeneratePartyData(
            Type: 1,
            Gender: PartyGender::Male,
            PartyAddressData: new PartyAddressData(
                RegionalDivisionRef: 10,
                Name: 'Main address',
                Details: 'Tehran',
            ),
            FirstName: 'Ali',
            LastName: 'Ahmadi',
            NationalID: '0012345678',
        );
        $voucher = new RegisterVoucherData(
            BranchRef: 1,
            Date: '2026-07-21',
            FiscalYearRef: 2,
            LedgerRef: 3,
            VoucherTypeRef: 4,
            VoucherTypeOwnerSystem: 'API',
            VoucherTypeCode: 5,
            VoucherItemData: [
                new VoucherItemData(
                    SLRef: 100,
                    Debit: 1000,
                    Credit: 0,
                    DL: '2001',
                    RowNumber: 1,
                ),
            ],
        );

        $this->assertSame(1, $party->toArray()['Gender']);
        $this->assertSame('', $party->toArray()['PartyAddressData']['Phone']);
        $this->assertSame(1, $voucher->toArray()['State']);
        $this->assertSame(1000.0, $voucher->toArray()['VoucherItemData'][0]['Debit']);
        $this->assertArrayHasKey('TaxAmount', $voucher->toArray()['VoucherItemData'][0]);
    }

    private function tokenPair(
        string $accessToken,
        string $refreshToken,
        int $expiresIn = 900,
    ): array {
        return [
            'token_type' => 'Bearer',
            'access_token' => $accessToken,
            'expires_in' => $expiresIn,
            'refresh_token' => $refreshToken,
            'refresh_expires_in' => 2_592_000,
        ];
    }

    private function dlPayload(): array
    {
        return [
            'Code' => '1001',
            'DLTypeRef' => 2,
            'Description' => 'Customer account',
            'ID' => 0,
            'ReferenceID' => 123,
            'Title' => 'Customer 123',
            'Title_En' => '',
        ];
    }
}
