<?php

namespace App\Services;

use App\DTO\GeneratePartyData;
use App\DTO\RegisterDLData;
use App\DTO\RegisterVoucherData;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PublicKey;
use phpseclib3\Math\BigInteger;
use Throwable;

class FinancialVoucherService
{
    protected string $baseUrl;

    protected ?CookieJarInterface $cookieJar = null;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.voucher.base_url'), '/');

        $this->login();
    }

    public function register_voucher(RegisterVoucherData $data)
    {
        return $this->httpPost(
            '/Financial/VoucherManagement/Services/VoucherService.svc/RegisterVoucher',
            $data->toArray()
        )->json();
    }

    public function register_dl(RegisterDLData $data)
    {
        return $this->httpPost(
            '/Financial/COAManagement/Services/COAService.svc/RegisterDL',
            $data->toArray()
        )->json();
    }

    public function generate_party(GeneratePartyData $data)
    {
        return $this->httpPost(
            '/Financial/PartyManagement/Services/PartyService.svc/GenerateParty',
            $data->toArray()
        )->json();
    }

    /**
     * Login to Rahkaran and record every diagnostic stage under one attempt ID.
     *
     * @throws \RuntimeException
     */
    private function login(): void
    {
        $attemptId = (string) Str::uuid();
        $startedAt = microtime(true);
        $stageStartedAt = $startedAt;
        $stage = 'configuration';
        $response = null;
        $cookieJar = new CookieJar;

        $username = config('services.voucher.username');
        $password = config('services.voucher.password');

        $context = [
            'event' => 'rahkaran.login.started',
            'auth_system' => 'rahkaran',
            'attempt_id' => $attemptId,
            'stage' => $stage,
            'base_url' => $this->safeBaseUrl(),
            'username_hint' => $this->maskUsername(is_string($username) ? $username : ''),
        ];

        Log::info('Rahkaran login started.', $context);

        try {
            if ($this->baseUrl === '' || ! filter_var($this->baseUrl, FILTER_VALIDATE_URL)) {
                throw $this->loginException($stage, $attemptId, 'AVAN_SEIR_API_URL is missing or invalid.');
            }

            if (! is_string($username) || trim($username) === '') {
                throw $this->loginException($stage, $attemptId, 'AVAN_SEIR_USERNAME is missing.');
            }

            if (! is_string($password) || $password === '') {
                throw $this->loginException($stage, $attemptId, 'AVAN_SEIR_PASSWORD is missing.');
            }

            // $this->logStageCompleted($context, $stage, $stageStartedAt);

            // The reference client reuses one CURL instance. A shared cookie
            // jar reproduces that behavior for session, login and API calls.
            $client = Http::acceptJson()->withOptions(['cookies' => $cookieJar]);

            $stage = 'session_request';
            $stageStartedAt = microtime(true);
            $response = $client->get(
                $this->baseUrl . '/Services/Framework/AuthenticationService.svc/session'
            );

            if ($response->failed()) {
                throw $this->loginException(
                    $stage,
                    $attemptId,
                    'Session endpoint returned an unsuccessful HTTP status.',
                    $response->status(),
                );
            }
            // $this->logStageCompleted($context, $stage, $stageStartedAt, [
            //     'http_status' => $response->status(),
            //     'cookie_count' => count($cookieJar),
            //     'cookie_names' => $this->cookieNames($cookieJar),
            // ]);

            $stage = 'session_validation';
            $stageStartedAt = microtime(true);
            $session = $response->json();
            if (
                ! is_array($session)
                || ! is_string($session['id'] ?? null)
                || ! is_string($session['rsa']['M'] ?? null)
                || ! is_string($session['rsa']['E'] ?? null)
            ) {
                throw $this->loginException(
                    $stage,
                    $attemptId,
                    'Session response is missing id, rsa.M, or rsa.E.',
                    $response->status(),
                );
            }
            // $this->logStageCompleted($context, $stage, $stageStartedAt);

            $stage = 'password_encryption';
            $stageStartedAt = microtime(true);
            $sessionId = $session['id'];
            $encryptedPassword = $this->encryptPassword(
                $session['rsa']['M'],
                $session['rsa']['E'],
                $sessionId . '**' . $password
            );
            // $this->logStageCompleted($context, $stage, $stageStartedAt);

            $stage = 'login_request';
            $stageStartedAt = microtime(true);
            // The AvanSeir login endpoint only accepts the payload as a raw
            // text/plain body; sending it as application/json (Http::post's
            // default) or multipart form-data is rejected.
            $response = $client
                ->withBody(json_encode([
                    'sessionId' => $sessionId,
                    'username' => $username,
                    'password' => $encryptedPassword,
                ], JSON_UNESCAPED_UNICODE), 'text/plain')
                ->post($this->baseUrl . '/Services/Framework/AuthenticationService.svc/login');

            if ($response->failed()) {
                throw $this->loginException(
                    $stage,
                    $attemptId,
                    'Login endpoint returned an unsuccessful HTTP status.',
                    $response->status(),
                );
            }
            // $this->logStageCompleted($context, $stage, $stageStartedAt, [
            //     'http_status' => $response->status(),
            //     'cookie_count' => count($cookieJar),
            //     'cookie_names' => $this->cookieNames($cookieJar),
            // ]);

            // The official PHP sample treats any non-empty login response as
            // an authentication error; a successful login returns no content.
            $stage = 'login_response';
            $stageStartedAt = microtime(true);
            if (trim($response->body()) !== '') {
                throw $this->loginException(
                    $stage,
                    $attemptId,
                    'Login endpoint returned a non-empty error response.',
                    $response->status(),
                );
            }

            $this->cookieJar = $cookieJar;
            // $this->logStageCompleted($context, $stage, $stageStartedAt);

            // Log::info('Rahkaran login succeeded.', array_merge($context, [
            //     'event' => 'rahkaran.login.succeeded',
            //     'stage' => 'completed',
            //     'duration_ms' => $this->elapsedMilliseconds($startedAt),
            //     'cookie_count' => count($cookieJar),
            //     'cookie_names' => $this->cookieNames($cookieJar),
            // ]));
        } catch (Throwable $exception) {
            $errorContext = array_merge($context, [
                'event' => 'rahkaran.login.failed',
                'stage' => $stage,
                'duration_ms' => $this->elapsedMilliseconds($startedAt),
                'stage_duration_ms' => $this->elapsedMilliseconds($stageStartedAt),
                'http_status' => $response instanceof Response ? $response->status() : null,
                'reason' => Str::limit($exception->getMessage(), 1000),
                'exception_class' => $exception::class,
            ]);

            if ($response instanceof Response) {
                $errorContext['response'] = $this->safeResponseContext($response);
            }

            Log::error('Rahkaran login failed.', $errorContext);

            throw $exception;
        }
    }

    /** @return list<string> */
    private function cookieNames(CookieJarInterface $cookieJar): array
    {
        $names = [];

        foreach ($cookieJar as $cookie) {
            $names[] = $cookie->getName();
        }

        return array_values(array_unique($names));
    }

    private function loginException(
        string $stage,
        string $attemptId,
        string $reason,
        ?int $httpStatus = null,
        ?Throwable $previous = null,
    ): \RuntimeException {
        $status = $httpStatus === null ? '' : " HTTP status: {$httpStatus}.";

        return new \RuntimeException(sprintf(
            'Rahkaran login failed at stage [%s] (reference: %s).%s %s',
            $stage,
            $attemptId,
            $status,
            $reason,
        ), previous: $previous);
    }

    private function logStageCompleted(array $context, string $stage, float $startedAt, array $extra = []): void
    {
        Log::info('Rahkaran login stage completed.', array_merge($context, $extra, [
            'event' => 'rahkaran.login.stage_completed',
            'stage' => $stage,
            'stage_duration_ms' => $this->elapsedMilliseconds($startedAt),
        ]));
    }

    private function elapsedMilliseconds(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function safeBaseUrl(): string
    {
        $parts = parse_url($this->baseUrl);

        if (! is_array($parts) || ! isset($parts['host'])) {
            return '[invalid]';
        }

        return ($parts['scheme'] ?? 'http') . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }

    private function maskUsername(string $username): string
    {
        $length = mb_strlen($username);

        if ($length === 0) {
            return '[missing]';
        }

        return mb_substr($username, 0, 1) . str_repeat('*', max(2, $length - 1));
    }

    /** @return array<string, int|string|null> */
    private function safeResponseContext(Response $response): array
    {
        $body = $response->body();
        $body = preg_replace(
            '/("(?:password|sessionId|sg-auth|sg-auth-AvanSeir|token|authorization|cookie)"\s*:\s*)"(?:\\\\.|[^"\\\\])*"/i',
            '$1"[REDACTED]"',
            $body,
        ) ?? '[unavailable]';

        $body = preg_replace(
            '/((?:password|sessionId|sg-auth|sg-auth-AvanSeir|token|authorization|cookie)\s*[=:]\s*)[^\s,;}]+/i',
            '$1[REDACTED]',
            $body,
        ) ?? '[unavailable]';

        return [
            'status' => $response->status(),
            'content_type' => $response->header('Content-Type'),
            'body' => Str::limit($body, 2000, '...[truncated]'),
        ];
    }

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

        return strtoupper(bin2hex($publicKey->encrypt($plainText)));
    }

    private function httpPost(string $url, array $data): PromiseInterface|Response
    {
        $request = Http::acceptJson();

        if ($this->cookieJar !== null) {
            $request = $request->withOptions(['cookies' => $this->cookieJar]);
        }

        Log::info('Rahkaran data posting', [...$data, 'url' => $this->baseUrl . $url]);

        $response = $request->withBody(json_encode($data, JSON_UNESCAPED_UNICODE))
            ->post($this->baseUrl . $url);

        Log::info('Rahkaran data response', [
            'body' => $response->body(),
        ]);

        return $response;
    }
}
