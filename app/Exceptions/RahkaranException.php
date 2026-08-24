<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * A failure reported by Rahkaran itself.
 *
 * Rahkaran answers business failures with an HTTP 200 whose body is a JSON
 * string holding a .NET exception dump, so the caller cannot tell them apart
 * from a successful registration. This exception turns such an answer into a
 * real error response carrying the Persian message Rahkaran produced.
 */
class RahkaranException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $rahkaranException = null,
        public readonly ?string $detail = null,
        public readonly int $upstreamStatus = 0,
        public readonly int $status = 422,
    ) {
        parent::__construct($message);
    }

    public static function fromResponse(int $upstreamStatus, string $body): self
    {
        $body = trim($body);
        $exceptionClass = null;
        $message = self::firstLines($body);

        if (preg_match('/^([A-Za-z0-9_.]*(?:Exception|Fault))\s*:\s*(.*)$/us', $message, $matches) === 1) {
            $exceptionClass = $matches[1];
            $message = trim($matches[2]);
        }

        return new self(
            message: $message === '' ? 'Rahkaran rejected the request without a message.' : $message,
            rahkaranException: $exceptionClass,
            detail: $body === '' ? null : $body,
            upstreamStatus: $upstreamStatus,
            // A parsed exception is a business rule failure; anything else is
            // an upstream transport or hosting problem.
            status: $exceptionClass !== null ? 422 : 502,
        );
    }

    public function render(Request $request): JsonResponse
    {
        $payload = [
            'message' => $this->getMessage(),
            'error' => 'rahkaran_error',
            'rahkaran' => array_filter([
                'exception' => $this->rahkaranException,
                'http_status' => $this->upstreamStatus,
            ], static fn (mixed $value): bool => $value !== null && $value !== 0),
        ];

        if (config('app.debug') && $this->detail !== null) {
            $payload['rahkaran']['detail'] = $this->detail;
        }

        return response()->json($payload, $this->status);
    }

    /**
     * Keep the human readable head of the dump: everything before the .NET
     * stack trace, with the line breaks folded away.
     */
    private static function firstLines(string $body): string
    {
        $head = preg_split('/\R\s*at\s+\S+/u', $body, 2)[0] ?? $body;
        $head = (string) preg_replace('/\R+/u', ' ', $head);
        $head = (string) preg_replace('/\s+([.,;:؛،])/u', '$1', $head);

        return trim((string) preg_replace('/\s{2,}/u', ' ', $head));
    }
}
