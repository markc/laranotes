<?php

namespace App\Services;

final class CollabTokenService
{
    private readonly string $secret;

    private readonly string $kid;

    private readonly int $ttl;

    public function __construct()
    {
        $this->secret = config('collab.hmac', config('collab.secret', ''));
        $this->kid = config('collab.kid', 'v1');
        $this->ttl = config('collab.ttl', 300);
    }

    public function mint(int $userId, int $noteId, bool $canEdit): string
    {
        $now = time();
        $payload = self::b64url(json_encode([
            'sub' => $userId,
            'note' => $noteId,
            'can_edit' => $canEdit,
            'exp' => $now + $this->ttl,
            'iat' => $now,
            'kid' => $this->kid,
        ]));

        $sig = self::b64url(hash_hmac('sha256', $payload, $this->secret, true));

        return "{$payload}.{$sig}";
    }

    public function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$payloadB64, $signature] = $parts;

        $payload = json_decode(self::b64decode($payloadB64), true);
        if (! $payload || empty($payload['kid']) || empty($payload['exp'])) {
            return null;
        }

        if (time() > $payload['exp']) {
            return null;
        }

        $expectedSig = self::b64url(hash_hmac('sha256', $payloadB64, $this->secret, true));

        if (! hash_equals($expectedSig, $signature)) {
            return null;
        }

        return $payload;
    }

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64decode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
