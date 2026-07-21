<?php

namespace App\Services;

use App\DTO\GeneratePartyData;
use App\DTO\RegisterDLData;
use App\DTO\RegisterVoucherData;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PublicKey;
use phpseclib3\Math\BigInteger;

class FinancialVoucherService
{
    protected string $baseUrl;

    protected ?string $cookie = null;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->baseUrl = config('services.voucher.base_url');

        $this->login();
    }

    //    ثبت سند حسابداری

    /**
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function register_voucher(RegisterVoucherData $data)
    {
        return $this->httpPost(
            '/Financial/VoucherManagement/Services/VoucherService.svc/RegisterVoucher',
            $data->toArray()
        )->json();
    }

    //    (بابت تفصیلی غیرسیستمی)

    /**
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function register_dl(RegisterDLData $data)
    {
        return $this->httpPost(
            '/Financial/COAManagement/Services/COAService.svc/RegisterDL',
            $data->toArray()
        )->json();
    }

    //    تعریف شخص

    /**
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function generate_party(GeneratePartyData $data)
    {
        return $this->httpPost(
            '/Financial/PartyManagement/Services/PartyService.svc/GenerateParty',
            $data->toArray()
        )->json();
    }

    /**
     * Login to Rahkaran
     *
     * @throws \Exception
     */
    private function login(): void
    {
        $username = config('services.voucher.username');
        $password = config('services.voucher.password');

        // Step 1 : Get Session
        $response = Http::acceptJson()
            ->get($this->baseUrl.'/Services/Framework/AuthenticationService.svc/session');

        if ($response->failed()) {
            throw new \Exception($response->body());
        }

        $session = $response->json();

        $sessionId = $session['id'];

        // Cookie returned from session
        $cookies = $response->header('Set-Cookie');

        // Step 2 : Encrypt password
        $encryptedPassword = $this->encryptPassword(
            $session['rsa']['M'],
            $session['rsa']['E'],
            $sessionId.'**'.$password
        );

        // Step 3 : Login
        $login = Http::acceptJson()
            ->withHeaders([
                'Cookie' => $cookies,
            ])
            ->post(
                $this->baseUrl.'/Services/Framework/AuthenticationService.svc/login',
                [
                    'sessionId' => $sessionId,
                    'username' => $username,
                    'password' => $encryptedPassword,
                ]
            );

        if ($login->failed()) {
            throw new \Exception($login->body());
        }

        $this->cookie = $login->header('Set-Cookie');
    }

    /**
     * RSA Encrypt like C#
     */
    private function encryptPassword(string $modulusHex, string $exponentHex, string $plainText): string
    {
        $modulus = new BigInteger($modulusHex, 16);
        $exponent = new BigInteger($exponentHex, 16);

        $key = PublicKeyLoader::load([
            'n' => $modulus,
            'e' => $exponent,
        ]);

        if (! $key instanceof PublicKey) {
            throw new \RuntimeException('Loaded key is not an RSA public key.');
        }

        $publicKey = $key->withPadding(RSA::ENCRYPTION_PKCS1);
        $encrypted = $publicKey->encrypt($plainText);

        return strtoupper(bin2hex($encrypted));
    }

    /**
     * POST with login cookie
     */
    private function httpPost(string $url, array $data): PromiseInterface|Response
    {
        return Http::acceptJson()
            ->withHeaders([
                'Cookie' => $this->cookie,
            ])
            ->post(
                $this->baseUrl.$url,
                $data
            );
    }
}
