# راهنمای اتصال پروژه Laravel به API مالی

این پوشه یک integration آماده کپی برای پروژه Laravel مصرف‌کننده است. دریافت و refresh توکن، نگهداری رمز‌شده توکن در Cache، retry بعد از خطای 401 و DTOهای مالی در آن پیاده‌سازی شده‌اند.

## نصب در پروژه دیگر

فایل‌های زیر را با همین مسیر در پروژه مقصد کپی کنید:

```text
app/Exceptions/FinancialApiException.php
app/Integrations/AvanseyrFinancial/
app/Services/FinancialApiClient.php
config/avanseyr-financial-api.php
```

تنظیمات زیر را به `.env` پروژه مقصد اضافه کنید:

```dotenv
AVANSEYR_FINANCIAL_API_URL=https://financial-api.example.com
AVANSEYR_FINANCIAL_CLIENT_ID=CLIENT_ID
AVANSEYR_FINANCIAL_CLIENT_SECRET=CLIENT_SECRET
AVANSEYR_FINANCIAL_CONNECT_TIMEOUT=5
AVANSEYR_FINANCIAL_TIMEOUT=30
AVANSEYR_FINANCIAL_VERIFY_TLS=true
AVANSEYR_FINANCIAL_TOKEN_CACHE_KEY=avanseyr-financial-api-token
```

در سرور API مالی یک client بسازید:

```bash
php artisan api-client:create "Accounting Backend"
```

سپس در پروژه مصرف‌کننده اجرا کنید:

```bash
php artisan config:clear
```

## نمونه ثبت سند

```php
use App\Integrations\AvanseyrFinancial\Data\RegisterVoucherData;
use App\Integrations\AvanseyrFinancial\Data\VoucherItemData;
use App\Integrations\AvanseyrFinancial\Enums\VoucherState;
use App\Services\FinancialApiClient;

$result = app(FinancialApiClient::class)->registerVoucher(
    new RegisterVoucherData(
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
                Debit: 1_000_000,
                SLCode: '8013125',
                DL5: '2001',
                DLTypeRef5: 2,
                Description: 'پرداخت فاکتور',
                RowNumber: 1,
            ),
        ],
        VoucherTypeOwnerSystem: 'API',
        Description: 'سند فاکتور ۱۸۴۵۲',
        State: VoucherState::WithNoRevision,
        IsExternal: true,
    ),
    'voucher-order-18452',
);
```

نکات مهم:

- در هر ردیف دقیقاً یکی از `Debit` یا `Credit` باید بزرگ‌تر از صفر باشد. فیلد مقابل را ارسال نکنید؛ مقدار صفر قدیمی نیز پیش از ارسال حذف می‌شود.
- سطح تفصیلی باید صریح ارسال شود؛ برای مثال اگر سطح ۵ اجباری است از `DL5` و `DLTypeRef5` استفاده کنید.
- فیلدهای قدیمی و مبهم `DL`، `DLTypeRef` و `DLLevelTitle` دیگر در DTO وجود ندارند.
- `SLRef` می‌تواند `null` باشد. تمام مقادیر اختیاری `null` پیش از ارسال حذف می‌شوند.
- مقادیر اختیاری `null` و صفرهای عددی از ردیف سند حذف می‌شوند، اما مقدار boolean برابر `false` حفظ می‌شود.
- تاریخ می‌تواند timestamp ثانیه‌ای یا رشته تاریخ باشد؛ تبدیل به قالب WCF در سرور API مالی انجام می‌شود.
- DTO از سطوح `DL4` تا `DL20` پشتیبانی می‌کند.

## دریافت خطاهای راهکاران

اگر `ValidationErrors` خالی نباشد، API مالی پاسخ 422 می‌دهد و integration یک `FinancialApiException` پرتاب می‌کند:

```php
use App\Exceptions\FinancialApiException;

try {
    $result = $client->registerVoucher($voucher);
} catch (FinancialApiException $exception) {
    foreach ($exception->validationErrors() as $error) {
        logger()->warning('خطای اعتبارسنجی راهکاران', [
            'key' => $error['key'] ?? null,
            'message' => $error['value'] ?? null,
        ]);
    }

    $status = $exception->statusCode();
    $response = $exception->responseData();
}
```

وجود `ID: 0` همراه با `ValidationErrors` یعنی سند ثبت نشده است.

## مهاجرت از نسخه قبلی

```php
// قبل
new VoucherItemData(
    SLRef: 100,
    Debit: 1_000_000,
    Credit: 0,
    VoucherItemID: 0,
    DL: '2001',
    DLTypeRef: 2,
);

// بعد
new VoucherItemData(
    Debit: 1_000_000,
    SLRef: 100,
    ID: 0,
    DL5: '2001',
    DLTypeRef5: 2,
);
```

در نسخه جدید `Number` و `Creator` اجباری هستند و فیلدهای اختیاری به‌جای صفر یا رشته خالی، مقدار پیش‌فرض `null` دارند.
