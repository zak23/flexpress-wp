<?php

declare(strict_types=1);

namespace FlexPress\Age;

/** Storage only: every protected request still requires GatewayClient::assertEligible(). */
final class Sessions
{
    public function __construct(private readonly Records $records)
    {
    }

    /** Call only with a verified proof. Clear the previous cookie before starting rotation. */
    public function create(#[\SensitiveParameter] array $proof, ?string $previousHandle = null): array
    {
        $now = time();
        if (!is_int($proof['expires_at'] ?? null) || !is_int($proof['age_expires_at'] ?? null) ||
            $proof['expires_at'] <= $now || $proof['expires_at'] > $proof['age_expires_at']) {
            throw new StorageUnavailable();
        }
        if ($previousHandle !== null) {
            $this->destroy($previousHandle);
        }
        $handle = self::random();
        $expiresAt = min($proof['expires_at'], $now + 3600);
        $proof['expires_at'] = $expiresAt;
        $this->records->put('session', hash('sha256', $handle), [
            'proof' => $proof, 'csrf' => self::random(), 'expires_at' => $expiresAt,
        ], $expiresAt);
        return ['handle' => $handle, 'expires_at' => $expiresAt];
    }

    public function read(string $handle): ?array
    {
        if (!self::validHandle($handle)) {
            return null;
        }
        return $this->records->get('session', hash('sha256', $handle), time());
    }

    /** Destroy locally before attempting remote revocation, even if the issuer is offline. */
    public function destroy(string $handle): void
    {
        if (self::validHandle($handle)) {
            $this->records->delete('session', hash('sha256', $handle));
        }
    }

    /** Origin/method checks are also required at the HTTP boundary. */
    public function csrfMatches(string $handle, string $submitted): bool
    {
        if (!self::validHandle($submitted)) {
            return false;
        }
        $session = $this->read($handle);
        return is_string($session['csrf'] ?? null) && hash_equals($session['csrf'], $submitted);
    }

    private static function validHandle(string $handle): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_-]{43}$/D', $handle);
    }

    private static function random(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
