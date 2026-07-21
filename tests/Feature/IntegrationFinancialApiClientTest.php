<?php

namespace Tests\Feature;

use App\Integrations\AvanseyrFinancial\Data\GeneratePartyData;
use App\Integrations\AvanseyrFinancial\Data\PartyAddressData;
use App\Integrations\AvanseyrFinancial\Data\RegisterDLData;
use App\Integrations\AvanseyrFinancial\Data\RegisterVoucherData;
use App\Integrations\AvanseyrFinancial\Data\VoucherItemData;
use App\Integrations\AvanseyrFinancial\Enums\PartyGender;
use App\Services\FinancialApiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

require_once __DIR__.'/../../integrations/laravel-client/app/Integrations/AvanseyrFinancial/Contracts/PayloadData.php';
require_once __DIR__.'/../../integrations/laravel-client/app/Integrations/AvanseyrFinancial/Enums/PartyGender.php';
require_once __DIR__.'/../../integrations/laravel-client/app/Integrations/AvanseyrFinancial/Enums/VoucherState.php';
require_once __DIR__.'/../../integrations/laravel-client/app/Integrations/AvanseyrFinancial/Data/PartyAddressData.php';
require_once __DIR__.'/../../integrations/laravel-client/app/Integrations/AvanseyrFinancial/Data/GeneratePartyData.php';
require_once __DIR__.'/../../integrations/laravel-client/app/Integrations/AvanseyrFinancial/Data/RegisterDLData.php';
require_once __DIR__.'/../../integrations/laravel-client/app/Integrations/AvanseyrFinancial/Data/VoucherItemData.php';
require_once __DIR__.'/../../integrations/laravel-client/app/Integrations/AvanseyrFinancial/Data/RegisterVoucherData.php';
require_once __DIR__.'/../../integrations/laravel-client/app/Exceptions/FinancialApiException.php';
require_once __DIR__.'/../../integrations/laravel-client/app/Services/FinancialApiClient.php';

class IntegrationFinancialApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'avanseyr-financial-api.base_url' => 'https://financial.test',
            'avanseyr-financial-api.client_id' => 'client-id',
            'avanseyr-financial-api.client_secret' => 'client-secret',
            'avanseyr-financial-api.cache_key' => 'integration-financial-client-test-token',
        ]);

        Cache::flush();
    }

    public function test_integration_accepts_dtos_and_sends_the_expected_payload(): void
    {
        Http::fake([
            'financial.test/api/v1/backend-auth/token' => Http::response($this->tokenPair()),
            'financial.test/api/v1/financial/dls' => Http::response(['ID' => 42]),
        ]);

        $result = app(FinancialApiClient::class)->registerDL(new RegisterDLData(
            Code: '1001',
            DLTypeRef: 2,
            Title: 'Customer 123',
        ));

        $this->assertSame(['ID' => 42], $result);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://financial.test/api/v1/financial/dls'
            && $request['Code'] === '1001'
            && $request['Description'] === ''
            && $request['Title_En'] === '');
    }

    public function test_integration_nested_dtos_generate_all_required_fields(): void
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
                new VoucherItemData(SLRef: 100, Debit: 1000, Credit: 0),
            ],
        );

        $this->assertSame(1, $party->toArray()['Gender']);
        $this->assertSame('', $party->toArray()['PartyAddressData']['Phone']);
        $this->assertSame(1, $voucher->toArray()['State']);
        $this->assertArrayHasKey('TaxAmount', $voucher->toArray()['VoucherItemData'][0]);
    }

    private function tokenPair(): array
    {
        return [
            'token_type' => 'Bearer',
            'access_token' => 'access-1',
            'expires_in' => 900,
            'refresh_token' => 'refresh-1',
            'refresh_expires_in' => 2_592_000,
        ];
    }
}
