# Historical payment acceptance evidence — release 1.0.39

These results belong to Zakspov and its authorized provider test instruments. They do not establish acceptance on another destination.

## What was verified on Zakspov

- Provider-issued test card on Zakspov: recurring membership USD 29.95 activated through its signed callback, then cancelled through Billing; signed cancellation preserved access through the paid-through date. Episode and Extras PPV at USD 29.95 each produced confirmed receipts and unlocked only their purchased items without granting membership. Fixed-duration two-day USD 4.95 membership activated with provider expiry and no rebill date; the final return displayed Payment confirmed with the correct receipt. Completed test notification queues drained.
- Regression coverage includes receipt reference encoding, fixed-duration routing, and clearing stale recurring dates. Isolated payment/signature/price/access regressions and notification adapters use no provider traffic.
- Actual provider rebill, uncancel, expiry, refund and chargeback events remain unverified; those cases are covered by synthetic regression tests. Test accounts and transactions remain identified locally for audit; the recurring test subscription was cancelled. Lifetime membership checkout is unsupported and rejected before payment.
- A private database backup restored to a disposable MySQL database; real WordPress transactions, injected receipt-write failure/rollback, replay, PPV and refund flows tested there with all outgoing HTTP/email intercepted.
- Caddy's actual installed version tested against 17 response scenarios; public dynamic-cache behavior checked separately.
- ZIP contents are selected from tracked files, omit development/backup files, include runtime vendor assets and generated combined CSS, and carry a file-hash manifest. The builder lints all shipped PHP and verifies ZIP integrity without changing source versions.
