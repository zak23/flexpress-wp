<?php

declare(strict_types=1);

namespace FlexPress\Age;

/** Loaded only after the approved private PHP client runtime has been loaded. */
final class Transactions implements \Exclusv\Age\TransactionStore
{
    public function __construct(private readonly Records $records)
    {
    }

    public function save(string $handleHash, #[\SensitiveParameter] array $transaction, int $expiresAt): void
    {
        $transaction['member_id'] = get_current_user_id();
        $this->records->put('transaction', $handleHash, $transaction, $expiresAt);
    }

    public function consume(string $handleHash, int $now): ?array
    {
        $transaction = $this->records->take('transaction', $handleHash, $now);
        // Never attach a flow started as a guest or another member to this account.
        return $transaction && ($transaction['member_id'] ?? null) === get_current_user_id() ? $transaction : null;
    }
}
