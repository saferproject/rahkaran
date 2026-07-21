# Laravel consumer client

This directory contains the client-side code for a trusted Laravel backend that consumes the financial API.

## Install

Copy the files into the consumer backend:

```text
app/Exceptions/FinancialApiException.php -> app/Exceptions/FinancialApiException.php
app/Services/FinancialApiClient.php       -> app/Services/FinancialApiClient.php
config/avanseyr-financial-api.php         -> config/avanseyr-financial-api.php
```

Add these values to the consumer backend's `.env`:

```dotenv
AVANSEYR_FINANCIAL_API_URL=https://financial-api.example.com
AVANSEYR_FINANCIAL_CLIENT_ID=the-client-id
AVANSEYR_FINANCIAL_CLIENT_SECRET=the-client-secret
AVANSEYR_FINANCIAL_CONNECT_TIMEOUT=5
AVANSEYR_FINANCIAL_TIMEOUT=30
AVANSEYR_FINANCIAL_VERIFY_TLS=true
```

Create the credentials once on the financial API server:

```bash
php artisan api-client:create "Accounting Backend"
```

Then clear the configuration cache on the consumer backend:

```bash
php artisan config:clear
```

Use a shared cache driver such as Redis if the consumer backend runs on multiple instances. Tokens are encrypted with Laravel's `APP_KEY` before being written to cache.

## Usage

Inject the service into a controller, job, or another service:

```php
use App\Services\FinancialApiClient;

class AccountingService
{
    public function __construct(
        private readonly FinancialApiClient $financialApi,
    ) {}

    public function registerDL(): mixed
    {
        return $this->financialApi->registerDL([
            'Code' => '1001',
            'DLTypeRef' => 2,
            'Description' => 'Customer account',
            'ID' => 0,
            'ReferenceID' => 123,
            'Title' => 'Customer 123',
            'Title_En' => '',
        ]);
    }
}
```

Available methods:

```php
$client->registerVoucher($payload);
$client->registerDL($payload);
$client->generateParty($payload);
$client->revoke();
```

Each operation also accepts an optional stable idempotency key:

```php
$client->registerVoucher($payload, 'voucher-order-18452');
```

The client automatically:

- obtains the first token pair using client credentials;
- caches access and refresh tokens in encrypted form;
- refreshes shortly before access-token expiration;
- rotates the refresh token and saves the new value;
- retries the financial request once after a `401` response;
- falls back to client credentials if the refresh token is expired or revoked;
- uses a cache lock to prevent concurrent refresh requests.

Never commit the client secret or generated tokens to source control. Do not disable TLS verification in production.
