<?php

namespace Tests\Feature;

use App\Exceptions\FinancialApiException;
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
use InvalidArgumentException;
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
            VoucherTypeCode: 5,
            Number: 18452,
            Creator: 10,
            VoucherItemData: [
                new VoucherItemData(
                    Debit: 1000,
                    Credit: 0,
                    SLCode: '8013125',
                    DL5: '2001',
                    DLTypeRef5: 2,
                ),
            ],
            VoucherTypeOwnerSystem: 'API',
        );

        $this->assertSame(1, $party->toArray()['Gender']);
        $this->assertSame('', $party->toArray()['PartyAddressData']['Phone']);
        $this->assertSame(1, $voucher->toArray()['State']);
        $this->assertSame('2001', $voucher->toArray()['VoucherItemData'][0]['DL5']);
        $this->assertArrayNotHasKey('DL4', $voucher->toArray()['VoucherItemData'][0]);
        $this->assertArrayNotHasKey('SLRef', $voucher->toArray()['VoucherItemData'][0]);
        $this->assertArrayNotHasKey('TaxAmount', $voucher->toArray()['VoucherItemData'][0]);
    }

    public function test_integration_sends_explicit_detail_levels_without_null_fields(): void
    {
        Http::fake([
            'financial.test/api/v1/backend-auth/token' => Http::response($this->tokenPair()),
            'financial.test/api/v1/financial/vouchers' => Http::response(['ID' => 99]),
        ]);

        app(FinancialApiClient::class)->registerVoucher(new RegisterVoucherData(
            BranchRef: 1,
            Date: 1765465335,
            FiscalYearRef: 2,
            LedgerRef: 3,
            VoucherTypeRef: 4,
            VoucherTypeCode: 5,
            Number: 18452,
            Creator: 10,
            VoucherItemData: [
                new VoucherItemData(
                    Debit: 1000,
                    Credit: 0,
                    SLCode: '8013125',
                    DL5: '2001',
                    DLTypeRef5: 2,
                ),
            ],
        ));

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://financial.test/api/v1/financial/vouchers') {
                return false;
            }

            $item = $request['VoucherItemData'][0];

            return $request['Date'] === 1765465335
                && $item['Debit'] === 1000.0
                && $item['Credit'] === 0.0
                && $item['DL5'] === '2001'
                && $item['DLTypeRef5'] === 2
                && ! array_key_exists('DL4', $item)
                && ! array_key_exists('SLRef', $item);
        });
    }

    public function test_integration_exposes_rahkaran_validation_errors(): void
    {
        Http::fake([
            'financial.test/api/v1/backend-auth/token' => Http::response($this->tokenPair()),
            'financial.test/api/v1/financial/vouchers' => Http::response([
                'ID' => 0,
                'ValidationErrors' => [[
                    'key' => null,
                    'value' => 'مقدار تفصیلی سطح 5 اجباری است',
                ]],
            ], 422),
        ]);

        try {
            app(FinancialApiClient::class)->registerVoucher(new RegisterVoucherData(
                BranchRef: 1,
                Date: '2026-07-21',
                FiscalYearRef: 2,
                LedgerRef: 3,
                VoucherTypeRef: 4,
                VoucherTypeCode: 5,
                Number: 18452,
                Creator: 10,
                VoucherItemData: [new VoucherItemData(Debit: 1000, Credit: 0)],
            ));

            $this->fail('A FinancialApiException was not thrown.');
        } catch (FinancialApiException $exception) {
            $this->assertSame(422, $exception->statusCode());
            $this->assertSame('مقدار تفصیلی سطح 5 اجباری است', $exception->getMessage());
            $this->assertCount(1, $exception->validationErrors());
        }
    }

    public function test_voucher_item_rejects_zero_debit_and_credit_before_sending(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one of Debit or Credit must be greater than zero.');

        new VoucherItemData(Debit: 0, Credit: 0);
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
