<?php

namespace Tests\Feature;

use App\DTO\GeneratePartyData;
use App\DTO\RegisterDLData;
use App\DTO\RegisterVoucherData;
use App\Models\ApiClient;
use App\Services\FinancialVoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class FinancialVoucherApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_backend_api_requires_an_access_token(): void
    {
        $this->postJson('/api/v1/financial/dls', [])
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_it_registers_a_dl(): void
    {
        $service = Mockery::mock(FinancialVoucherService::class);
        $service->shouldReceive('register_dl')
            ->once()
            ->with(Mockery::on(fn ($data): bool => $data instanceof RegisterDLData
                && $data->Code === '1001'
                && $data->DLTypeRef === 2))
            ->andReturn(['ID' => 42]);
        $this->app->instance(FinancialVoucherService::class, $service);

        $this->authenticateClient('dls:create')
            ->postJson('/api/v1/financial/dls', [
                'Code' => '1001',
                'DLTypeRef' => 2,
                'Description' => 'Test DL',
                'ID' => 0,
                'ReferenceID' => 12,
                'Title' => 'Test',
                'Title_En' => '',
            ])
            ->assertOk()
            ->assertExactJson(['ID' => 42]);
    }

    public function test_it_generates_a_party(): void
    {
        $service = Mockery::mock(FinancialVoucherService::class);
        $service->shouldReceive('generate_party')
            ->once()
            ->with(Mockery::on(fn ($data): bool => $data instanceof GeneratePartyData
                && $data->Gender->value === 1
                && $data->PartyAddressData->IsMainAddress))
            ->andReturn(['PartyID' => 15]);
        $this->app->instance(FinancialVoucherService::class, $service);

        $this->authenticateClient('parties:create')
            ->postJson('/api/v1/financial/parties', [
                'ID' => 0,
                'Type' => 1,
                'FirstName' => 'Ali',
                'LastName' => 'Ahmadi',
                'FirstName_EN' => 'Ali',
                'LastName_EN' => 'Ahmadi',
                'CompanyName' => '',
                'CompanyName_EN' => '',
                'Alias' => '',
                'NationalID' => '0012345678',
                'EconomicCode' => '',
                'Gender' => 1,
                'PartyAddressData' => [
                    'ID' => 0,
                    'IsMainAddress' => true,
                    'RegionalDivisionRef' => 1,
                    'Name' => 'Main',
                    'Details' => 'Tehran',
                    'Details_En' => '',
                    'Phone' => '',
                    'ZipCode' => '',
                    'Email' => '',
                    'Fax' => '',
                    'WebPage' => '',
                ],
            ])
            ->assertOk()
            ->assertExactJson(['PartyID' => 15]);
    }

    public function test_it_registers_a_voucher(): void
    {
        $service = Mockery::mock(FinancialVoucherService::class);
        $service->shouldReceive('register_voucher')
            ->once()
            ->with(Mockery::on(fn ($data): bool => $data instanceof RegisterVoucherData
                && $data->State->value === 1
                && count($data->VoucherItemData) === 1))
            ->andReturn(['VoucherID' => 99]);
        $this->app->instance(FinancialVoucherService::class, $service);

        $this->authenticateClient('vouchers:create')
            ->postJson('/api/v1/financial/vouchers', $this->voucherPayload())
            ->assertOk()
            ->assertExactJson(['VoucherID' => 99]);
    }

    private function authenticateClient(string $ability): static
    {
        $client = ApiClient::query()->create([
            'name' => 'Test backend',
            'client_id' => fake()->uuid(),
            'client_secret_hash' => Hash::make('secret'),
            'abilities' => [$ability],
        ]);

        return $this->withToken(
            $client->createToken('test-token', [$ability], now()->addMinutes(15))->plainTextToken
        );
    }

    private function voucherPayload(): array
    {
        return [
            'BranchRef' => 1,
            'Date' => '2026-07-21',
            'Description' => 'Test voucher',
            'Description_En' => '',
            'FiscalYearRef' => 1,
            'LedgerRef' => 1,
            'VoucherTypeRef' => 1,
            'VoucherTypeOwnerSystem' => 'API',
            'VoucherTypeCode' => 1,
            'Number' => 1,
            'AuxiliaryNumber' => '',
            'IsCurrentBased' => false,
            'State' => 1,
            'IsExternal' => true,
            'Creator' => 1,
            'CreatorName' => 'API',
            'StateTitle' => '',
            'VoucherItemData' => [[
                'VoucherItemID' => 0,
                'SLRef' => 1,
                'Debit' => 1000,
                'Credit' => 0,
                'CurrencyAmount' => 1000,
                'BaseCurrencyAmount' => 1000,
                'CurrencyCredit' => 0,
                'CurrencyDebit' => 1000,
                'CurrencyRef' => 1,
                'BaseCurrencyRef' => 1,
                'OperationalCurrencyExchangeRate' => 1,
                'OperationalCurrencyExchangeRateRef' => 1,
                'BaseCurrencyExchangeRate' => 1,
                'BaseCurrencyExchangeRateRef' => 1,
                'DL' => '1001',
                'DLTypeRef' => 1,
                'Description' => 'Test row',
                'Description_En' => '',
                'FollowUpDate' => '',
                'FollowUpNumber' => '',
                'RowNumber' => 1,
                'SLCode' => '1101',
                'ExtraData' => '',
                'TaxAccountType' => 0,
                'TransactionType' => 0,
                'TaxStateType' => 0,
                'PurchaseOrSale' => 0,
                'ItemOrService' => 0,
                'PartyRef' => 0,
                'TaxAmount' => 0,
                'TollAmount' => 0,
                'SLTitle' => '',
                'IsSLTraceable' => false,
                'OperationalCurrencyPrecision' => 0,
                'DLLevelTitle' => '',
                'CurrencyPrecision' => 0,
                'CurrencyTitle' => '',
                'BaseCurrencyPrecision' => 0,
                'BaseCurrencyTitle' => '',
                'NumberOfSLDLLevels' => 0,
                'Quantity' => 1,
            ]],
        ];
    }
}
