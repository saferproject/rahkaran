<?php

namespace Tests\Feature;

use App\DTO\RegisterDLData;
use App\Exceptions\RahkaranException;
use App\Services\FinancialVoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use phpseclib3\Crypt\RSA;
use Tests\TestCase;

class RahkaranErrorResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.voucher.base_url' => 'https://rahkaran.test',
            'services.voucher.username' => 'diagnostic-user',
            'services.voucher.password' => 'never-log-this-password',
        ]);
    }

    public function test_a_business_failure_answered_with_http_200_becomes_an_error(): void
    {
        $this->fakeRahkaran(json_encode(
            "SystemGroup.Framework.Exceptions.SgException: معین (های) 8013125,8013130 دارای ویژگی پیگیری نمی باشند\r\n.\r\n"
            .'   at SystemGroup.Financial.VoucherManagement.Business.VoucherBusiness.InternalSave(Voucher& record, Boolean batchSave) in D:\\VoucherBusiness.cs:line 1903',
            JSON_UNESCAPED_UNICODE,
        ));

        try {
            (new FinancialVoucherService)->register_dl($this->dl());
            $this->fail('Expected the Rahkaran failure to be raised.');
        } catch (RahkaranException $exception) {
            $this->assertSame('معین (های) 8013125,8013130 دارای ویژگی پیگیری نمی باشند.', $exception->getMessage());
            $this->assertSame('SystemGroup.Framework.Exceptions.SgException', $exception->rahkaranException);
            $this->assertSame(422, $exception->status);

            config(['app.debug' => false]);
            $response = $exception->render(Request::create('/api/v1/financial/dls'));

            $this->assertSame(422, $response->getStatusCode());
            $this->assertSame([
                'message' => 'معین (های) 8013125,8013130 دارای ویژگی پیگیری نمی باشند.',
                'error' => 'rahkaran_error',
                'rahkaran' => [
                    'exception' => 'SystemGroup.Framework.Exceptions.SgException',
                    'http_status' => 200,
                ],
            ], $response->getData(true));

            config(['app.debug' => true]);
            $debugged = $exception->render(Request::create('/api/v1/financial/dls'))->getData(true);

            $this->assertStringContainsString('VoucherBusiness.cs:line 1903', $debugged['rahkaran']['detail']);
        }
    }

    public function test_an_upstream_failure_without_an_exception_dump_becomes_a_bad_gateway(): void
    {
        $this->fakeRahkaran('<html>Service Unavailable</html>', 503);

        try {
            (new FinancialVoucherService)->register_dl($this->dl());
            $this->fail('Expected the Rahkaran failure to be raised.');
        } catch (RahkaranException $exception) {
            $this->assertSame(502, $exception->status);
            $this->assertSame(503, $exception->upstreamStatus);
            $this->assertNull($exception->rahkaranException);
        }
    }

    public function test_a_successful_answer_is_returned_unchanged(): void
    {
        $this->fakeRahkaran(json_encode(['ID' => 42]));

        $this->assertSame(['ID' => 42], (new FinancialVoucherService)->register_dl($this->dl()));
    }

    private function fakeRahkaran(string $body, int $status = 200): void
    {
        $key = RSA::createKey(1024)->getPublicKey()->toString('Raw');

        Http::fakeSequence()
            ->push([
                'id' => 'session-id',
                'rsa' => [
                    'M' => $key['n']->toHex(),
                    'E' => $key['e']->toHex(),
                ],
            ], 200, ['Set-Cookie' => 'sg-session=session-cookie; Path=/; HttpOnly'])
            ->push('', 200, ['Set-Cookie' => 'sg-auth=secret-cookie; Path=/; HttpOnly'])
            ->push($body, $status, ['Content-Type' => 'application/json']);
    }

    private function dl(): RegisterDLData
    {
        return new RegisterDLData(
            Code: '1001',
            DLTypeRef: 2,
            Description: 'Test',
            ID: 0,
            ReferenceID: 1,
            Title: 'Test',
            Title_En: 'Test',
        );
    }
}
