<?php

declare(strict_types=1);

namespace FlexPress\Age;

/** Private, bounded storage. Use a dedicated wpdb connection, never the payment transaction connection. */
final class Records
{
    private string $table;

    private function __construct(private readonly Database $db, private readonly RecordCipher $cipher, string $prefix)
    {
        if (!preg_match('/^[A-Za-z0-9_]{1,40}$/D', $prefix) || strlen($prefix . 'flexpress_age_records_limits') > 64) {
            throw new StorageUnavailable();
        }
        $this->table = $prefix . 'flexpress_age_records';
        $this->db->suppress_errors(true);
    }

    /** Own the connection so payment/plugin transactions cannot roll back a consumed handoff. */
    public static function connect(
        string $user,
        #[\SensitiveParameter] string $password,
        string $database,
        string $host,
        RecordCipher $cipher,
        string $prefix
    ): self {
        try {
            return new self(new Database($user, $password, $database, $host), $cipher, $prefix);
        } catch (\Throwable) {
            throw new StorageUnavailable();
        }
    }

    /** Explicit deployment migration; never run DDL during a visitor request. */
    public function install(): void
    {
        $this->autocommit();
        $this->query($this->db->prepare(
            'CREATE TABLE IF NOT EXISTS %i (
                kind varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                handle_hash char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                expires_at bigint unsigned NOT NULL,
                payload mediumblob NOT NULL,
                claim char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
                PRIMARY KEY (kind, handle_hash), KEY expires_at (expires_at)
            ) ENGINE=InnoDB', $this->table
        ));
        $this->query($this->db->prepare(
            'CREATE TABLE IF NOT EXISTS %i (
                bucket char(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
                used bigint unsigned NOT NULL,
                expires_at bigint unsigned NOT NULL,
                KEY expires_at (expires_at)
            ) ENGINE=InnoDB', $this->table . '_limits'
        ));
    }

    /** Atomic fixed-window limit. Bucket is a keyed hash, never a raw visitor IP. */
    public function allow(string $bucket, int $maximum, int $seconds): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $bucket) || $maximum < 1 || $maximum > 1000 || $seconds < 1 || $seconds > 3600) {
            throw new StorageUnavailable();
        }
        $this->autocommit();
        $now = time();
        $this->query($this->db->prepare(
            'INSERT INTO %i (bucket, used, expires_at) VALUES (%s, LAST_INSERT_ID(1), %d)
             ON DUPLICATE KEY UPDATE used = LAST_INSERT_ID(IF(expires_at <= %d, 1, LEAST(used + 1, 1001))),
             expires_at = IF(expires_at <= %d, %d, expires_at)',
            $this->table . '_limits', $bucket, $now + $seconds, $now, $now, $now + $seconds
        ));
        // Connection-local result avoids races with the next visitor's increment.
        $row = $this->row('SELECT LAST_INSERT_ID() AS used');
        return $row !== null && (int) $row['used'] <= $maximum;
    }

    public function purgeLimits(): int
    {
        $this->autocommit();
        return $this->query($this->db->prepare(
            'DELETE FROM %i WHERE expires_at <= %d ORDER BY expires_at LIMIT 500', $this->table . '_limits', time()
        ));
    }

    public function put(string $kind, string $hash, #[\SensitiveParameter] array $payload, int $expiresAt): void
    {
        $this->validate($kind, $hash);
        $now = time();
        if ($expiresAt <= $now || $expiresAt > $now + ($kind === 'transaction' ? 600 : ($kind === 'member' ? 90 * 86400 : 3600))) {
            throw new StorageUnavailable();
        }
        $envelope = $this->cipher->seal($payload, $this->binding($kind, $hash, $expiresAt));
        $this->autocommit();
        // INSERT, never REPLACE: a repeated browser handle must not restore a consumed flow.
        if ($this->query($this->db->prepare(
            'INSERT INTO %i (kind, handle_hash, expires_at, payload) VALUES (%s, %s, %d, %s)',
            $this->table, $kind, $hash, $expiresAt, $envelope
        )) !== 1) {
            throw new StorageUnavailable();
        }
    }

    public function get(string $kind, string $hash, int $now): ?array
    {
        $this->validate($kind, $hash);
        $this->autocommit();
        $row = $this->row($this->db->prepare(
            'SELECT expires_at, payload FROM %i WHERE kind = %s AND handle_hash = %s AND claim IS NULL AND expires_at > %d',
            $this->table, $kind, $hash, $now
        ));
        return $row === null ? null : $this->decode($kind, $hash, $row);
    }

    /** One atomic claim wins across workers. A crash after claiming leaves the flow unusable. */
    public function take(string $kind, string $hash, int $now): ?array
    {
        $this->validate($kind, $hash);
        $this->autocommit();
        $claim = bin2hex(random_bytes(32));
        if ($this->query($this->db->prepare(
            'UPDATE %i SET claim = %s WHERE kind = %s AND handle_hash = %s AND claim IS NULL AND expires_at > %d',
            $this->table, $claim, $kind, $hash, $now
        )) !== 1) {
            return null;
        }
        $row = $this->row($this->db->prepare(
            'SELECT expires_at, payload FROM %i WHERE kind = %s AND handle_hash = %s AND claim = %s',
            $this->table, $kind, $hash, $claim
        ));
        // Delete before returning any verifier. A failed delete must not yield usable credentials.
        if ($this->query($this->db->prepare(
            'DELETE FROM %i WHERE kind = %s AND handle_hash = %s AND claim = %s',
            $this->table, $kind, $hash, $claim
        )) !== 1 || $row === null) {
            throw new StorageUnavailable();
        }
        return $this->decode($kind, $hash, $row);
    }

    public function delete(string $kind, string $hash): void
    {
        $this->validate($kind, $hash);
        $this->autocommit();
        $this->query($this->db->prepare(
            'DELETE FROM %i WHERE kind = %s AND handle_hash = %s', $this->table, $kind, $hash
        ));
    }

    /** Bounded maintenance, including abandoned claimed flows; no remote provider calls. */
    public function purge(int $now, int $limit = 500): int
    {
        if ($limit < 1 || $limit > 1000) {
            throw new StorageUnavailable();
        }
        $this->autocommit();
        return $this->query($this->db->prepare(
            'DELETE FROM %i WHERE expires_at <= %d ORDER BY expires_at LIMIT %d', $this->table, $now, $limit
        ));
    }

    private function decode(string $kind, string $hash, array $row): array
    {
        return $this->cipher->open($row['payload'], $this->binding($kind, $hash, (int) $row['expires_at']));
    }

    private function binding(string $kind, string $hash, int $expiresAt): string
    {
        return $this->table . ':' . $kind . ':' . $hash . ':' . $expiresAt;
    }

    private function validate(string $kind, string $hash): void
    {
        if (!in_array($kind, ['transaction', 'session', 'member'], true) || !preg_match('/^[a-f0-9]{64}$/D', $hash)) {
            throw new StorageUnavailable();
        }
    }

    private function autocommit(): void
    {
        $row = $this->row('SELECT @@session.autocommit AS enabled');
        if ($row === null || (int) $row['enabled'] !== 1) {
            throw new StorageUnavailable();
        }
    }

    private function query(string $sql): int
    {
        try {
            $result = $this->db->query($sql);
            if ($result === false) {
                throw new StorageUnavailable();
            }
            return (int) $result;
        } catch (\Throwable) {
            throw new StorageUnavailable();
        }
    }

    private function row(string $sql): ?array
    {
        try {
            $row = $this->db->get_row($sql, ARRAY_A);
            if ($this->db->last_error !== '') {
                throw new StorageUnavailable();
            }
            return $row;
        } catch (\Throwable) {
            throw new StorageUnavailable();
        }
    }
}
