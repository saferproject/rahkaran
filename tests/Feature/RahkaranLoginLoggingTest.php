<?php

namespace Tests\Feature;

use App\DTO\RegisterDLData;
use App\Services\FinancialVoucherService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use phpseclib3\Crypt\RSA;
use RuntimeException;
use Tests\TestCase;

class RahkaranLoginLoggingTest extends TestCase
{
    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = storage_path('framework/testing/rahkaran-login-test.log');
        @unlink($this->logPath);

        config([
            'services.voucher.base_url' => 'https://rahkaran.test',
            'services.voucher.username' => 'diagnostic-user',
            'services.voucher.password' => 'never-log-this-password',
            'logging.default' => 'single',
            'logging.channels.single.path' => $this->logPath,
        ]);

        Log::forgetChannel('single');
    }

    protected function tearDown(): void
    {
        Log::forgetChannel('single');
        @unlink($this->logPath);

        parent::tearDown();
    }

    public function test_failed_session_request_creates_safe_diagnostics(): void
    {
        Http::fake([
            'https://rahkaran.test/*' => Http::response([
                'message' => 'upstream unavailable',
                'password' => 'echoed-password',
                'token' => 'echoed-token',
            ], 503, ['Content-Type' => 'application/json']),
        ]);

        try {
            new FinancialVoucherService;
            $this->fail('Expected Rahkaran authentication to fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('session_request', $exception->getMessage());
            $this->assertStringContainsString('HTTP status: 503', $exception->getMessage());
            $this->assertStringContainsString('reference:', $exception->getMessage());
        }

        Log::forgetChannel('single');
        $log = file_get_contents($this->logPath);

        $this->assertIsString($log);
        $this->assertStringContainsString('rahkaran.login.failed', $log);
        $this->assertStringContainsString('session_request', $log);
        $this->assertStringContainsString('[REDACTED]', $log);
        $this->assertStringNotContainsString('never-log-this-password', $log);
        $this->assertStringNotContainsString('echoed-password', $log);
        $this->assertStringNotContainsString('echoed-token', $log);
    }

    public function test_successful_login_records_all_stages_without_cookie_or_password(): void
    {
        $key = RSA::createKey(1024)->getPublicKey()->toString('Raw');

        Http::fakeSequence()
            ->push([
                'id' => 'session-id',
                'rsa' => [
                    'M' => $key['n']->toHex(),
                    'E' => $key['e']->toHex(),
                ],
            ], 200, [
                'Set-Cookie' => 'sg-session=session-cookie; Path=/; HttpOnly',
            ])
            ->push('', 200, [
                'Set-Cookie' => [
                    'other=value; Path=/',
                    'sg-auth=secret-cookie; Path=/; HttpOnly',
                ],
            ])
            ->push(['ID' => 42]);

        $service = new FinancialVoucherService;
        $result = $service->register_dl(new RegisterDLData(
            Code: '1001',
            DLTypeRef: 2,
            Description: 'Test',
            ID: 0,
            ReferenceID: 1,
            Title: 'Test',
            Title_En: 'Test',
        ));

        $this->assertSame(['ID' => 42], $result);
        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/login')
            && str_contains(implode('; ', $request->header('Cookie')), 'sg-session=session-cookie'));
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/RegisterDL')
            && str_contains(implode('; ', $request->header('Cookie')), 'sg-session=session-cookie')
            && str_contains(implode('; ', $request->header('Cookie')), 'sg-auth=secret-cookie'));
        Log::forgetChannel('single');
        $log = file_get_contents($this->logPath);

        $this->assertIsString($log);
        $this->assertStringContainsString('rahkaran.login.succeeded', $log);
        $this->assertStringContainsString('password_encryption', $log);
        $this->assertStringContainsString('login_response', $log);
        $this->assertStringContainsString('sg-session', $log);
        $this->assertStringContainsString('sg-auth', $log);
        $this->assertStringNotContainsString('never-log-this-password', $log);
        $this->assertStringNotContainsString('secret-cookie', $log);
        $this->assertStringNotContainsString('session-id', $log);
    }
}
