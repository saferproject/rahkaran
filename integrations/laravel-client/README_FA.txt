فایل‌های اصلی:
app/Services/FinancialApiClient.php
app/Exceptions/FinancialApiException.php
config/avanseyr-financial-api.php
روی سرور API ابتدا client بسازید:
php artisan api-client:create "Accounting Backend"
سپس اطلاعات خروجی را در .env بک‌اند مصرف‌کننده قرار دهید:
AVANSEYR_FINANCIAL_API_URL=https://financial-api.example.com
AVANSEYR_FINANCIAL_CLIENT_ID=CLIENT_ID
AVANSEYR_FINANCIAL_CLIENT_SECRET=CLIENT_SECRET
AVANSEYR_FINANCIAL_CONNECT_TIMEOUT=5
AVANSEYR_FINANCIAL_TIMEOUT=30
AVANSEYR_FINANCIAL_VERIFY_TLS=true
بعد از انتقال:
php artisan config:clear
نمونه استفاده:
use App\Services\FinancialApiClient;

class AccountingService
{
public function \_\_construct(
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
متدهای موجود:
$client->registerVoucher($payload);
$client->registerDL($payload);
$client->generateParty($payload);
$client->revoke();
این سرویس به‌صورت خودکار:
access و refresh token را دریافت می‌کند.
توکن‌ها را به‌شکل رمز‌شده در Cache نگه می‌دارد.
پیش از انقضا access token را refresh می‌کند.
refresh token جدید را جایگزین قبلی می‌کند.
پس از پاسخ 401 فقط یک بار توکن و درخواست را تجدید می‌کند.
در صورت نامعتبرشدن refresh token، با client credentials توکن جدید می‌گیرد.
از refresh هم‌زمان چند worker جلوگیری می‌کند.
نتیجه نهایی: ۲۰ تست و ۵۷ assertion موفق.
