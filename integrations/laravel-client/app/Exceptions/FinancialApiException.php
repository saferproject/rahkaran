<?php

namespace App\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

class FinancialApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
        private readonly array $responseData = [],
    ) {
        parent::__construct($message, $statusCode ?? 0);
    }

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();
        $data = is_array($data) ? $data : [];
        $message = $data['message'] ?? null;

        if (is_array($data['ValidationErrors'] ?? null)) {
            $messages = array_values(array_filter(array_map(
                static fn (mixed $error): ?string => is_array($error) && is_string($error['value'] ?? null)
                    ? $error['value']
                    : null,
                $data['ValidationErrors'],
            )));

            if ($messages !== []) {
                $message = implode(PHP_EOL, $messages);
            }
        }

        if (! is_string($message) || $message === '') {
            $message = 'Financial API request failed.';
        }

        return new self($message, $response->status(), $data);
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }

    /** @return list<array{key?: mixed, value?: mixed}> */
    public function validationErrors(): array
    {
        $errors = $this->responseData['ValidationErrors'] ?? [];

        return is_array($errors) ? array_values($errors) : [];
    }

    /** @return array<string, mixed> */
    public function responseData(): array
    {
        return $this->responseData;
    }
}
