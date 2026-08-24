# Laravel financial API integration

This directory is a copy-ready client for a trusted Laravel backend. It handles authentication, encrypted token caching, automatic token refresh, retries, and typed financial payloads.

## Requirements

- PHP 8.2 or newer
- Laravel 11 or newer
- A shared cache such as Redis when the application has multiple workers

## Install in another project

Copy these paths into the same locations in the consumer project:

```text
app/Exceptions/FinancialApiException.php
app/Integrations/AvanseyrFinancial/
app/Services/FinancialApiClient.php
config/avanseyr-financial-api.php
```

Add the connection settings to `.env`:

```dotenv
AVANSEYR_FINANCIAL_API_URL=https://financial-api.example.com
AVANSEYR_FINANCIAL_CLIENT_ID=the-client-id
AVANSEYR_FINANCIAL_CLIENT_SECRET=the-client-secret
AVANSEYR_FINANCIAL_CONNECT_TIMEOUT=5
AVANSEYR_FINANCIAL_TIMEOUT=30
AVANSEYR_FINANCIAL_VERIFY_TLS=true
AVANSEYR_FINANCIAL_TOKEN_CACHE_KEY=avanseyr-financial-api-token
```

Create credentials on the financial API server:

```bash
php artisan api-client:create "Accounting Backend"
```

Clear the consumer application's configuration cache after copying the config or changing `.env`:

```bash
php artisan config:clear
```

Never commit client secrets or cached tokens. Keep TLS verification enabled in production.

## Register a voucher

Use explicit detail-level fields. Do not use the old ambiguous `DL`, `DLTypeRef`, or `DLLevelTitle` properties.

```php
use App\Integrations\AvanseyrFinancial\Data\RegisterVoucherData;
use App\Integrations\AvanseyrFinancial\Data\VoucherItemData;
use App\Integrations\AvanseyrFinancial\Enums\VoucherState;
use App\Services\FinancialApiClient;

final class AccountingService
{
    public function __construct(
        private readonly FinancialApiClient $financialApi,
    ) {}

    public function registerVoucher(): mixed
    {
        return $this->financialApi->registerVoucher(new RegisterVoucherData(
            BranchRef: 1,
            Date: 1765465335, // Unix seconds or a date string such as 2026-07-21
            FiscalYearRef: 2,
            LedgerRef: 3,
            VoucherTypeRef: 4,
            VoucherTypeCode: 5,
            Number: 18452,
            Creator: 10,
            VoucherItemData: [
                new VoucherItemData(
                    Debit: 1_000_000,
                    SLCode: '8013125',
                    DL5: '2001',
                    DLTypeRef5: 2,
                    Description: 'Invoice payment',
                    RowNumber: 1,
                ),
            ],
            VoucherTypeOwnerSystem: 'API',
            Description: 'Invoice 18452',
            State: VoucherState::WithNoRevision,
            IsExternal: true,
        ), 'voucher-order-18452');
    }
}
```

Important accounting rules:

- Exactly one of `Debit` or `Credit` must be greater than zero in every item. Omit the other field; a legacy zero is normalized and not sent.
- Send only the detail levels allowed for the selected account.
- When Rahkaran requires level 5, provide `DL5` and its matching `DLTypeRef5`; do not put that code in `DL4`.
- `SLRef` may be omitted or `null`. If it is null, the key is not sent.
- All optional `null` and numeric zero values are recursively removed from voucher items. Boolean `false` values are preserved.
- The financial API converts `Date` and `FollowUpDate` to Rahkaran's WCF date format.

The DTO supports `DL4` through `DL20`, their corresponding `DLTypeRef4` through `DLTypeRef20`, and `DLLevel4Title` through `DLLevel20Title`.

## Handle Rahkaran validation errors

Rahkaran business validation failures are returned by the financial API with HTTP status `422`. The client throws `FinancialApiException` and exposes the original errors:

```php
use App\Exceptions\FinancialApiException;

try {
    $result = $accountingService->registerVoucher();
} catch (FinancialApiException $exception) {
    report($exception);

    foreach ($exception->validationErrors() as $error) {
        logger()->warning('Rahkaran validation error', [
            'key' => $error['key'] ?? null,
            'message' => $error['value'] ?? null,
        ]);
    }

    $status = $exception->statusCode(); // 422
    $response = $exception->responseData();
}
```

An `ID` of `0` together with a non-empty `ValidationErrors` array means the voucher was not registered.

## Other operations

```php
$client->registerVoucher($payload, $idempotencyKey);
$client->registerDL($payload, $idempotencyKey);
$client->generateParty($payload, $idempotencyKey);
$client->revoke();
```

Each payload can be a typed DTO or an array. Prefer DTOs for vouchers because they prevent ambiguous detail-level fields.

## Migration from the previous integration

Update voucher item construction as follows:

```php
// Before (ambiguous and no longer supported)
new VoucherItemData(
    SLRef: 100,
    Debit: 1_000_000,
    Credit: 0,
    VoucherItemID: 0,
    DL: '2001',
    DLTypeRef: 2,
);

// After (explicit Rahkaran fields)
new VoucherItemData(
    Debit: 1_000_000,
    SLRef: 100,
    ID: 0,
    DL5: '2001',
    DLTypeRef5: 2,
);
```

`Number` and `Creator` are now required constructor arguments. Optional values default to `null` instead of artificial zero or empty-string values.
