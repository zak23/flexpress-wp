<?php

declare(strict_types=1);

namespace FlexPress\Age;

/** Site-specific keys live outside WordPress options, theme files and the web root. */
final class RecordCipher
{
    private array $keys;

    public function __construct(#[\SensitiveParameter] array $keys, private readonly string $activeKey)
    {
        if (!extension_loaded('sodium') || !$keys || count($keys) > 3 || !isset($keys[$activeKey])) {
            throw new StorageUnavailable();
        }
        foreach ($keys as $id => $key) {
            if (!is_string($id) || !preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,31}$/D', $id) ||
                !is_string($key) || strlen($key) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
                throw new StorageUnavailable();
            }
        }
        $this->keys = $keys;
    }

    public function seal(#[\SensitiveParameter] array $payload, string $binding): string
    {
        try {
            $plain = json_encode($payload, JSON_THROW_ON_ERROR);
            if (strlen($plain) > 16384) {
                throw new StorageUnavailable();
            }
            $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
            $header = 'v1:' . $this->activeKey . ':';
            $encrypted = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
                $plain, $header . $binding, $nonce, $this->keys[$this->activeKey]
            );
            sodium_memzero($plain);
            return $header . base64_encode($nonce . $encrypted);
        } catch (\Throwable) {
            throw new StorageUnavailable();
        }
    }

    public function open(string $envelope, string $binding): array
    {
        try {
            if (strlen($envelope) > 22000) {
                throw new StorageUnavailable();
            }
            $parts = explode(':', $envelope, 3);
            if (count($parts) !== 3 || $parts[0] !== 'v1' || !isset($this->keys[$parts[1]])) {
                throw new StorageUnavailable();
            }
            $bytes = base64_decode($parts[2], true);
            $nonceLength = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
            if ($bytes === false || strlen($bytes) < $nonceLength + SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES) {
                throw new StorageUnavailable();
            }
            $plain = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
                substr($bytes, $nonceLength), 'v1:' . $parts[1] . ':' . $binding,
                substr($bytes, 0, $nonceLength), $this->keys[$parts[1]]
            );
            if ($plain === false) {
                throw new StorageUnavailable();
            }
            $payload = json_decode($plain, true, 32, JSON_THROW_ON_ERROR);
            sodium_memzero($plain);
            if (!is_array($payload)) {
                throw new StorageUnavailable();
            }
            return $payload;
        } catch (\Throwable) {
            throw new StorageUnavailable();
        }
    }
}
