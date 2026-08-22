<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LoginRequest extends FormRequest
{
    private ?string $loginAttemptId = null;

    private ?float $loginStartedAt = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $this->startLoginAudit();

        Log::warning('Web login input validation failed.', array_merge($this->loginContext(), [
            'event' => 'web.login.failed',
            'stage' => 'validation',
            'reason' => 'invalid_input',
            'invalid_fields' => array_keys($validator->errors()->toArray()),
            'duration_ms' => $this->loginDuration(),
        ]));

        parent::failedValidation($validator);
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->startLoginAudit();

        Log::info('Web login started.', array_merge($this->loginContext(), [
            'event' => 'web.login.started',
            'stage' => 'credentials',
        ]));

        $this->ensureIsNotRateLimited();

        try {
            $authenticated = Auth::attempt(
                $this->only('email', 'password'),
                $this->boolean('remember'),
            );
        } catch (Throwable $exception) {
            Log::error('Web login encountered an internal error.', array_merge($this->loginContext(), [
                'event' => 'web.login.failed',
                'stage' => 'credentials',
                'reason' => 'authentication_provider_error',
                'exception_class' => $exception::class,
                'exception_message' => Str::limit($exception->getMessage(), 500),
                'duration_ms' => $this->loginDuration(),
            ]));

            throw $exception;
        }

        if (! $authenticated) {
            RateLimiter::hit($this->throttleKey());

            Log::warning('Web login credentials were rejected.', array_merge($this->loginContext(), [
                'event' => 'web.login.failed',
                'stage' => 'credentials',
                'reason' => 'invalid_credentials',
                'failed_attempts' => RateLimiter::attempts($this->throttleKey()),
                'duration_ms' => $this->loginDuration(),
            ]));

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        Log::info('Web login credentials verified.', array_merge($this->loginContext(), [
            'event' => 'web.login.credentials_verified',
            'stage' => 'credentials',
            'user_id' => Auth::id(),
            'duration_ms' => $this->loginDuration(),
        ]));
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $this->startLoginAudit();
        Log::warning('Web login was rate limited.', array_merge($this->loginContext(), [
            'event' => 'web.login.failed',
            'stage' => 'rate_limit',
            'reason' => 'too_many_attempts',
            'retry_after_seconds' => $seconds,
            'duration_ms' => $this->loginDuration(),
        ]));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip());
    }

    public function logSessionEstablished(): void
    {
        Log::info('Web login succeeded.', array_merge($this->loginContext(), [
            'event' => 'web.login.succeeded',
            'stage' => 'session',
            'user_id' => Auth::id(),
            'duration_ms' => $this->loginDuration(),
        ]));
    }

    public function logSessionFailure(Throwable $exception): void
    {
        Log::error('Web login session creation failed.', array_merge($this->loginContext(), [
            'event' => 'web.login.failed',
            'stage' => 'session',
            'reason' => 'session_regeneration_error',
            'exception_class' => $exception::class,
            'exception_message' => Str::limit($exception->getMessage(), 500),
            'duration_ms' => $this->loginDuration(),
        ]));
    }

    private function startLoginAudit(): void
    {
        $this->loginAttemptId ??= (string) Str::uuid();
        $this->loginStartedAt ??= microtime(true);
    }

    /** @return array<string, bool|string|null> */
    private function loginContext(): array
    {
        $this->startLoginAudit();
        $email = Str::lower(trim((string) $this->input('email')));

        return [
            'auth_system' => 'web',
            'attempt_id' => $this->loginAttemptId,
            'email_hash' => hash_hmac('sha256', $email, (string) config('app.key')),
            'ip' => $this->ip(),
            'user_agent' => Str::limit((string) $this->userAgent(), 300),
            'remember' => $this->boolean('remember'),
        ];
    }

    private function loginDuration(): int
    {
        return (int) round((microtime(true) - ($this->loginStartedAt ?? microtime(true))) * 1000);
    }
}
