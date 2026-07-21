<?php

namespace App\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

class FinancialApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
    ) {
        parent::__construct($message, $statusCode ?? 0);
    }

    public static function fromResponse(Response $response): self
    {
        $message = $response->json('message');

        if (! is_string($message) || $message === '') {
            $message = 'Financial API request failed.';
        }

        return new self($message, $response->status());
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }
}
