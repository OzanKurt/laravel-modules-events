<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Support;

use Illuminate\Support\Str;

final class QrTokenSigner
{
    public function __construct(private readonly string $appKey) {}

    public function sign(int $ticketId, int $eventId, ?int $issuedAt = null): string
    {
        $payload = json_encode([
            'ticket_id' => $ticketId,
            'event_id' => $eventId,
            'issued_at' => $issuedAt ?? time(),
            'nonce' => Str::random(16),
        ], JSON_THROW_ON_ERROR);

        $b64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $sig = hash_hmac('sha256', $payload, $this->appKey);

        return "{$b64}.{$sig}";
    }

    /** @return array{ticket_id: int, event_id: int, issued_at: int, nonce: string} */
    public function verify(string $token): array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException('Malformed token');
        }
        [$b64, $sig] = $parts;

        $padded = $b64.str_repeat('=', (4 - strlen($b64) % 4) % 4);
        $payload = base64_decode(strtr($padded, '-_', '+/'), true);
        if ($payload === false) {
            throw new \InvalidArgumentException('Invalid base64 payload');
        }

        $expected = hash_hmac('sha256', $payload, $this->appKey);
        if (! hash_equals($expected, $sig)) {
            throw new \InvalidArgumentException('Signature mismatch');
        }

        /** @var array{ticket_id: int, event_id: int, issued_at: int, nonce: string} $data */
        $data = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

        return $data;
    }
}
